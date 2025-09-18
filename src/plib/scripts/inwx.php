<?php
// INWX DNS integration script for Plesk custom DNS backend
// Reads JSON instructions from STDIN as described in TUTORIAL.md and applies them to INWX nameservers via DomRobot API.

pm_Loader::registerAutoload();
pm_Context::init('inwx');

// Fallback to Composer autoloader if INWX classes are not found via pm_Loader
if (!class_exists('INWX\\Domrobot')) {
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (file_exists($autoload)) {
        require_once $autoload;
    }
}

use INWX\Domrobot;

// Exit early if extension not enabled (future-proof; no setting means enabled)
if (function_exists('pm_Settings::get') && pm_Settings::get('enabled') === '0') {
    exit(0);
}

function inwx_log($level, $message)
{
    // Simple stdout logger compatible with Plesk log capturing
    fwrite($level === 'err' ? STDERR : STDOUT, sprintf("[%s] %s\n", strtoupper($level), $message));
}

function inwx_client(): ?Domrobot
{
    // Create and authenticate INWX Domrobot client
    $username = pm_Settings::get('inwx_username') ?: getenv('INWX_USERNAME');
    $password = pm_Settings::get('inwx_password') ?: getenv('INWX_PASSWORD');
    $sharedSecret = pm_Settings::get('inwx_2fa_secret') ?: getenv('INWX_2FA_SECRET'); // optional
    $useLive = (pm_Settings::get('inwx_live') === '1') || (getenv('INWX_LIVE') === '1'); // '1' to use live endpoint

    if (!$username || !$password) {
        inwx_log('warn', 'INWX credentials are not configured. Skipping any DNS synchronization.');
        return null;
    }

    $domrobot = new Domrobot();
    if ($useLive) {
        $domrobot->useLive();
    } else {
        $domrobot->useOte();
    }
    $domrobot->useJson();

    try {
        $login = $domrobot->login($username, $password, $sharedSecret ?: null);
        if (!isset($login['code']) || (int)$login['code'] !== 1000) {
            inwx_log('err', 'INWX login failed: ' . json_encode($login));
            return null;
        }
    } catch (Exception $e) {
        inwx_log('err', 'INWX login exception: ' . $e->getMessage());
        return null;
    }

    return $domrobot;
}

function ensure_zone(Domrobot $client, string $zoneName): ?int
{
    // Return roId of zone if exists. Do NOT create automatically.
    // INWX expects zone names without trailing dot
    $name = rtrim($zoneName, '.');

    try {
        $info = $client->call('nameserver', 'info', ['domain' => $name]);
        if (isset($info['code']) && (int)$info['code'] === 1000 && isset($info['resData']['roId'])) {
            return (int)$info['resData']['roId'];
        }
        inwx_log('err', "Zone does not exist on INWX (auto-creation disabled): {$zoneName}");
    } catch (Exception $e) {
        inwx_log('err', 'INWX nameserver.info exception: ' . $e->getMessage());
    }

    return null;
}

function delete_zone(Domrobot $client, string $zoneName): bool
{
    $name = rtrim($zoneName, '.');
    try {
        $res = $client->call('nameserver', 'delete', ['domain' => $name]);
        if (isset($res['code']) && (int)$res['code'] === 1000) {
            inwx_log('info', "Zone deleted: {$zoneName}");
            return true;
        }
        inwx_log('warn', 'INWX nameserver.delete returned non-success: ' . json_encode($res));
    } catch (Exception $e) {
        inwx_log('err', 'INWX zone delete exception: ' . $e->getMessage());
    }
    return false;
}

function fetch_zone_records(Domrobot $client, int $roId): array
{
    try {
        $info = $client->call('nameserver', 'info', ['roId' => $roId]);
        if (isset($info['code']) && (int)$info['code'] === 1000) {
            return $info['resData']['record'] ?? [];
        }
    } catch (Exception $e) {
        inwx_log('err', 'INWX nameserver.info exception: ' . $e->getMessage());
    }
    return [];
}

function purge_zone_records(Domrobot $client, int $roId): void
{
    $records = fetch_zone_records($client, $roId);
    foreach ($records as $r) {
        $type = strtoupper($r['type'] ?? '');
        if ($type === 'SOA' || $type === 'NS') {
            continue;
        }
        $id = (int)$r['id'];
        try {
            $res = $client->call('nameserver', 'deleteRecord', ['id' => $id]);
            if (!isset($res['code']) || (int)$res['code'] !== 1000) {
                inwx_log('warn', 'Failed to delete record id ' . $id . ': ' . json_encode($res));
            }
        } catch (Exception $e) {
            inwx_log('err', 'INWX deleteRecord exception for id ' . $id . ': ' . $e->getMessage());
        }
    }
}

function create_record(Domrobot $client, int $roId, object $rr, int $defaultTtl): void
{
    $type = strtoupper($rr->type);
    if ($type === 'SOA') { return; }

    // Prefer rr->ttl if present, else default
    $ttl = isset($rr->ttl) ? (int)$rr->ttl : $defaultTtl;

    $params = [
        'roId' => $roId,
        'type' => $type,
        'ttl' => $ttl,
    ];

    // Plesk supplies FQDNs with trailing dot; INWX typically accepts both
    if (!empty($rr->host)) {
        $params['name'] = rtrim($rr->host, '.');
    }

    // Priority handling for MX and SRV
    $prioTypes = ['MX', 'SRV'];
    if (in_array($type, $prioTypes, true)) {
        $opt = isset($rr->opt) && $rr->opt !== '' ? (int)$rr->opt : 0;
        $params['prio'] = $opt;
    }

    // Content/value formatting
    $value = (string)($rr->value ?? '');
    if ($type === 'TXT') {
        // INWX accepts raw TXT without wrapping quotes. Normalize whitespace.
        $value = str_replace("\t", ' ', $value);
    }

    if ($type === 'CAA') {
        // For CAA, Plesk passes value like 'letsencrypt.org'; opt may contain flag and tag in one
        // Plesk passes opt as flag+tag (e.g., "0 issue"). If present, prepend it.
        $optPrefix = '';
        if (isset($rr->opt) && $rr->opt !== '') {
            $optPrefix = trim((string)$rr->opt) . ' ';
        }
        $params['content'] = $optPrefix . '"' . $value . '"';
    } elseif ($type === 'SRV') {
        // For SRV, INWX expects: prio (separate) and content as "weight port target"
        // Plesk provides rr->opt as "priority weight port" and rr->value as target
        $priority = null; $weight = null; $port = null;
        $optStr = isset($rr->opt) ? trim((string)$rr->opt) : '';
        if ($optStr !== '') {
            $parts = preg_split('/\s+/', $optStr);
            if (isset($parts[0]) && is_numeric($parts[0])) { $priority = (int)$parts[0]; }
            if (isset($parts[1]) && is_numeric($parts[1])) { $weight = (int)$parts[1]; }
            if (isset($parts[2]) && is_numeric($parts[2])) { $port = (int)$parts[2]; }
        }
        if ($priority !== null) {
            $params['prio'] = $priority;
        } else {
            // fallback to previous behavior if only single number present in opt
            if (isset($rr->opt) && $rr->opt !== '') {
                $params['prio'] = (int)$rr->opt;
            } else {
                $params['prio'] = 0;
            }
        }
        // Build content
        $tgt = rtrim($value, '.');
        $w = $weight !== null ? $weight : 0;
        $p = $port !== null ? $port : 0;
        $params['content'] = $w . ' ' . $p . ' ' . $tgt . '.';
    } else {
        // For most types, INWX expects pure content; for MX/NS/CNAME/A/AAAA, ensure no trailing dot duplication
        $params['content'] = $value;
    }

    try {
        $res = $client->call('nameserver', 'createRecord', $params);
        if (!isset($res['code']) || (int)$res['code'] !== 1000) {
            inwx_log('warn', 'Failed to create record ' . ($params['name'] ?? '') . ' ' . $type . ': ' . json_encode($res));
        }
    } catch (Exception $e) {
        inwx_log('err', 'INWX createRecord exception for ' . ($params['name'] ?? '') . ' ' . $type . ': ' . $e->getMessage());
    }
}

// Read JSON from stdin
$payload = stream_get_contents(STDIN);
$data = json_decode($payload);
if (!is_array($data)) {
    inwx_log('err', 'Invalid input to INWX DNS script. Expecting JSON array.');
    exit(255);
}

$client = inwx_client();
if (!$client) {
    // No credentials or login failed: exit 0 to avoid blocking Plesk operations, but warn
    inwx_log('warn', 'INWX client unavailable; skipping synchronization.');
    exit(0);
}

$hasErrors = false;

foreach ($data as $record) {
    if (!isset($record->command)) { continue; }

    if (isset($record->zone) && isset($record->zone->name)) {
        $zoneName = (string)$record->zone->name;
    } else {
        $zoneName = '';
    }

    switch ($record->command) {
        case 'create':
        case 'update':
            if (!$zoneName) { continue 2; }
            $roId = ensure_zone($client, $zoneName);
            if (!$roId) { $hasErrors = true; continue 2; }

            // Purge existing records except SOA/NS
            purge_zone_records($client, $roId);

            // Create new records from Plesk
            $defaultTtl = isset($record->zone->soa->ttl) ? (int)$record->zone->soa->ttl : 3600;
            if (isset($record->zone->rr) && is_array($record->zone->rr)) {
                foreach ($record->zone->rr as $rr) {
                    // Skip unsupported record types in INWX if any
                    $supportedTypes = ['A','AAAA','CNAME','MX','NS','TXT','SRV','CAA','PTR','SSHFP','TLSA','NSEC','DNAME','DNSKEY','DS','HINFO','LOC','NAPTR','RP','SPF','SRV','TLSA','URI'];
                    if (!in_array(strtoupper($rr->type), $supportedTypes, true)) {
                        continue;
                    }
                    // Don't manage NS at apex to avoid conflicts with INWX default NS, unless explicitly desired
                    if (strtoupper($rr->type) === 'NS') {
                        continue;
                    }
                    create_record($client, $roId, $rr, $defaultTtl);
                }
            }
            inwx_log('info', 'Zone synchronized: ' . $zoneName);
            break;

        case 'delete':
            if (!$zoneName) { continue 2; }
            if (!delete_zone($client, $zoneName)) { $hasErrors = true; }
            break;

        case 'createPTRs':
        case 'deletePTRs':
            // Managing PTRs requires reverse zones delegation which generally cannot be managed in INWX by this API unless owned.
            // We ignore PTR operations to avoid failures.
            inwx_log('info', 'PTR operation skipped for INWX backend: ' . $record->command);
            break;

        default:
            inwx_log('warn', 'Unknown command: ' . $record->command);
            break;
    }
}

try {
    $client->logout();
} catch (Exception $e) {
    // ignore
}

if ($hasErrors) {
    exit(255);
}
exit(0);
