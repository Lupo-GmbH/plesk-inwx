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

function normalize_rr_params(object $rr, int $defaultTtl): ?array
{
	$type = strtoupper($rr->type ?? '');
	if ($type === '' || $type === 'SOA') {
		return null;
	}

	$ttl = isset($rr->ttl) ? (int)$rr->ttl : $defaultTtl;

	$params = [
		'type' => $type,
		'ttl'  => $ttl,
	];

	if (!empty($rr->host)) {
		$params['name'] = rtrim($rr->host, '.');
	}

	// Priority handling for MX and SRV
	$prioTypes = ['MX', 'SRV'];
	if (in_array($type, $prioTypes, true)) {
		$opt = isset($rr->opt) && $rr->opt !== '' ? (int)$rr->opt : 0;
		$params['prio'] = $opt;
	}

	$value = (string)($rr->value ?? '');
	if ($type === 'TXT') {
		$value = str_replace("\t", ' ', $value);
	}

	if ($type === 'CAA') {
		$optPrefix = '';
		if (isset($rr->opt) && $rr->opt !== '') {
			$optPrefix = trim((string)$rr->opt) . ' ';
		}
		$params['content'] = $optPrefix . '"' . $value . '"';
	} elseif ($type === 'SRV') {
		$priority = null;
		$weight = null;
		$port = null;
		$optStr = isset($rr->opt) ? trim((string)$rr->opt) : '';
		if ($optStr !== '') {
			$parts = preg_split('/\s+/', $optStr);
			if (isset($parts[0]) && is_numeric($parts[0])) {
				$priority = (int)$parts[0];
			}
			if (isset($parts[1]) && is_numeric($parts[1])) {
				$weight = (int)$parts[1];
			}
			if (isset($parts[2]) && is_numeric($parts[2])) {
				$port = (int)$parts[2];
			}
		}
		if ($priority !== null) {
			$params['prio'] = $priority;
		} else {
			if (isset($rr->opt) && $rr->opt !== '') {
				$params['prio'] = (int)$rr->opt;
			} else {
				$params['prio'] = 0;
			}
		}
		$tgt = rtrim($value, '.');
		$w = $weight !== null ? $weight : 0;
		$p = $port !== null ? $port : 0;
		$params['content'] = $w . ' ' . $p . ' ' . $tgt . '.';
	} else {
		$params['content'] = $value;
	}

	return $params;
}

function create_record(Domrobot $client, int $roId, object $rr, int $defaultTtl): void
{
	$params = normalize_rr_params($rr, $defaultTtl);
	if ($params === null) {
		return;
	}
	$type = $params['type'];

	$params['roId'] = $roId;

	try {
		$res = $client->call('nameserver', 'createRecord', $params);
		if (!isset($res['code']) || (int)$res['code'] !== 1000) {
			inwx_log(
				'warn',
				'Failed to create record ' . ($params['name'] ?? '') . ' ' . $type . ': ' . json_encode($res)
			);
		}
	} catch (Exception $e) {
		inwx_log(
			'err',
			'INWX createRecord exception for ' . ($params['name'] ?? '') . ' ' . $type . ': ' . $e->getMessage()
		);
	}
}

function update_record(Domrobot $client, int $id, array $params): void
{
	$payload = ['id' => $id];
	foreach (['name', 'type', 'content', 'prio', 'ttl'] as $k) {
		if (isset($params[$k])) {
			$payload[$k] = $params[$k];
		}
	}
	try {
		$res = $client->call('nameserver', 'updateRecord', $payload);
		if (!isset($res['code']) || (int)$res['code'] !== 1000) {
			inwx_log('warn', 'Failed to update record id ' . $id . ': ' . json_encode($res));
		}
	} catch (Exception $e) {
		inwx_log('err', 'INWX updateRecord exception for id ' . $id . ': ' . $e->getMessage());
	}
}

function delete_record(Domrobot $client, int $id): void
{
	try {
		$res = $client->call('nameserver', 'deleteRecord', ['id' => $id]);
		if (!isset($res['code']) || (int)$res['code'] !== 1000) {
			inwx_log('warn', 'Failed to delete record id ' . $id . ': ' . json_encode($res));
		}
	} catch (Exception $e) {
		inwx_log('err', 'INWX deleteRecord exception for id ' . $id . ': ' . $e->getMessage());
	}
}

function sync_zone_records_incremental(Domrobot $client, int $roId, array $rrList, int $defaultTtl): void
{
	// Build desired params list
	$supportedTypes = [
		'A',
		'AAAA',
		'CNAME',
		'MX',
		'NS',
		'TXT',
		'SRV',
		'CAA',
		'PTR',
		'SSHFP',
		'TLSA',
		'NSEC',
		'DNAME',
		'DNSKEY',
		'DS',
		'HINFO',
		'LOC',
		'NAPTR',
		'RP',
		'SPF',
		'SRV',
		'TLSA',
		'URI',
	];
	$desired = [];
	foreach ($rrList as $rr) {
		$type = strtoupper($rr->type ?? '');
		if (!in_array($type, $supportedTypes, true)) {
			continue;
		}
		if ($type === 'SOA' || $type === 'NS') {
			continue;
		}
		$p = normalize_rr_params($rr, $defaultTtl);
		if ($p) {
			$desired[] = $p;
		}
	}

	// Fetch existing records
	$existing = fetch_zone_records($client, $roId);
	$existingFiltered = [];
	foreach ($existing as $r) {
		$t = strtoupper($r['type'] ?? '');
		if ($t === 'SOA' || $t === 'NS') {
			continue;
		}
		$existingFiltered[] = $r;
	}

	$keyExact = function ($type, $name, $prio, $content, $ttl) {
		$n = strtolower($name ?? '');
		$p = ($prio !== null) ? (int)$prio : 0;
		$c = (string)$content;
		$tt = (int)$ttl;

		return $type . '|' . $n . '|' . $p . '|' . $c . '|' . $tt;
	};
	$keyBase = function ($type, $name, $prio) {
		$n = strtolower($name ?? '');
		$p = ($prio !== null) ? (int)$prio : 0;

		return $type . '|' . $n . '|' . $p;
	};

	// Determine which base-keys are managed by Plesk in this sync run
	$desiredBaseKeys = [];
	foreach ($desired as $p) {
		$kB = $keyBase($p['type'], $p['name'] ?? '', $p['prio'] ?? null);
		$desiredBaseKeys[$kB] = true;
	}

	$existingByExact = [];
	$existingByBase = [];
	foreach ($existingFiltered as $r) {
		$kE = $keyExact(
			strtoupper($r['type'] ?? ''),
			$r['name'] ?? '',
			$r['prio'] ?? null,
			$r['content'] ?? '',
			$r['ttl'] ?? 0
		);
		$existingByExact[$kE] = $r;
		$kB = $keyBase(strtoupper($r['type'] ?? ''), $r['name'] ?? '', $r['prio'] ?? null);
		if (!isset($existingByBase[$kB])) {
			$existingByBase[$kB] = [];
		}
		$existingByBase[$kB][] = $r;
	}

	// 1) Skip exact matches
	$remainingDesired = [];
	foreach ($desired as $p) {
		$kE = $keyExact($p['type'], $p['name'] ?? '', $p['prio'] ?? null, $p['content'] ?? '', $p['ttl'] ?? 0);
		if (isset($existingByExact[$kE])) {
			// matched; remove from pools so they are not deleted
			$matched = $existingByExact[$kE];
			unset($existingByExact[$kE]);
			// also remove from base list
			$kB = $keyBase($p['type'], $p['name'] ?? '', $p['prio'] ?? null);
			if (isset($existingByBase[$kB])) {
				foreach ($existingByBase[$kB] as $idx => $item) {
					if (($item['id'] ?? null) === ($matched['id'] ?? null)) {
						unset($existingByBase[$kB][$idx]);
						break;
					}
				}
				if (empty($existingByBase[$kB])) {
					unset($existingByBase[$kB]);
				}
			}
			continue;
		}
		$remainingDesired[] = $p;
	}

	// 2) Updates or Creates for remaining desired
	foreach ($remainingDesired as $p) {
		$kB = $keyBase($p['type'], $p['name'] ?? '', $p['prio'] ?? null);
		if (isset($existingByBase[$kB]) && !empty($existingByBase[$kB])) {
			// Update the first matching existing record
			$existingRec = array_shift($existingByBase[$kB]);
			$id = (int)$existingRec['id'];
			update_record($client, $id, $p);
			// Remove also from exact pool if present in another representation
			$kEExisting = $keyExact(
				strtoupper($existingRec['type'] ?? ''),
				$existingRec['name'] ?? '',
				$existingRec['prio'] ?? null,
				$existingRec['content'] ?? '',
				$existingRec['ttl'] ?? 0
			);
			unset($existingByExact[$kEExisting]);
			if (empty($existingByBase[$kB])) {
				unset($existingByBase[$kB]);
			}
		} else {
			// Create new
			$payload = $p;
			$payload['roId'] = $roId;
			try {
				$res = $client->call('nameserver', 'createRecord', $payload);
				if (!isset($res['code']) || (int)$res['code'] !== 1000) {
					inwx_log(
						'warn',
						'Failed to create record ' . ($p['name'] ?? '') . ' ' . $p['type'] . ': ' . json_encode(
							$res
						)
					);
				}
			} catch (Exception $e) {
				inwx_log(
					'err',
					'INWX createRecord exception for ' . ($p['name'] ?? '') . ' ' . $p['type'] . ': ' . $e->getMessage()
				);
			}
		}
	}

	// 3) Delete any remaining existing records not desired, but only within base-keys managed by Plesk
	foreach ($existingByExact as $k => $r) {
		if (!isset($r['id'])) {
			continue;
		}
		$kB = $keyBase(strtoupper($r['type'] ?? ''), $r['name'] ?? '', $r['prio'] ?? null);
		if (!isset($desiredBaseKeys[$kB])) {
			// Non-conflicting INWX-only record; keep it
			continue;
		}
		delete_record($client, (int)$r['id']);
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
	if (!isset($record->command)) {
		continue;
	}

	if (isset($record->zone) && isset($record->zone->name)) {
		$zoneName = (string)$record->zone->name;
	} else {
		$zoneName = '';
	}

	switch ($record->command) {
		case 'create':
		case 'update':
			if (!$zoneName) {
				continue 2;
			}
			$roId = ensure_zone($client, $zoneName);
			if (!$roId) {
				$hasErrors = true;
				continue 2;
			}

			// Incremental sync of records (no purge)
			$defaultTtl = isset($record->zone->soa->ttl) ? (int)$record->zone->soa->ttl : 3600;
			if (isset($record->zone->rr) && is_array($record->zone->rr)) {
				sync_zone_records_incremental($client, $roId, $record->zone->rr, $defaultTtl);
			}
			inwx_log('info', 'Zone synchronized incrementally: ' . $zoneName);
			break;

		case 'delete':
			if (!$zoneName) {
				continue 2;
			}
			if (!delete_zone($client, $zoneName)) {
				$hasErrors = true;
			}
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
