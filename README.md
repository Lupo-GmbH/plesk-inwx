# INWX DNS Backend for Plesk

This repository provides a minimal Plesk extension that synchronizes DNS zones from Plesk to INWX nameservers using the official DomRobot API. It implements Plesk’s “custom DNS backend” model: whenever DNS zones change in Plesk, the changes are pushed to INWX.

## Paths on the Plesk host

- Path to extension entry points:
  `/opt/psa/admin/htdocs/modules/inwx/`
- Path to PHP classes:
  `/opt/psa/admin/plib/modules/inwx/`
- Path to installation scripts:
  `/opt/psa/admin/plib/modules/inwx/scripts/`
- Path to run-time data directory:
  `/opt/psa/var/modules/inwx/`

## What you get

- Incremental synchronization of DNS records on INWX when domains change in Plesk. Only the necessary create/update/delete operations are performed.
- Zones are not created automatically at INWX. If a zone does not exist at INWX, the operation is reported as an error and no changes are applied.
- Non‑conflicting records that exist only at INWX are preserved. On conflicts, Plesk’s data takes precedence.
- Record mapping for common types: A, AAAA, CNAME, MX, TXT, SRV, CAA, …
- Safety defaults: SOA and NS records are not changed by this backend; PTR operations from Plesk are ignored.

## Requirements

- Plesk Obsidian 18.x or newer on Linux or Windows.
- PHP 7.4 or 8.x available on the Plesk host (Plesk ships PHP for extensions).
- An INWX account (live or OTE/sandbox) and API access.

## Repository layout

- `src/plib/scripts/inwx.php` — the core synchronizer invoked by Plesk.
- `src/plib/scripts/post-install.php` — registers the custom DNS backend on install.
- `src/plib/scripts/pre-uninstall.php` — unregisters the backend on uninstall.
- `src/plib/vendor` — Composer vendor directory (autoloader + DomRobot client).
- `TUTORIAL.md` — Plesk’s specification for custom DNS backends.
- `INWX-API.md` — extracted reference for the INWX DomRobot API.

## Installation

### 1) Build and install the extension

- Package the extension directory (`src`) into a zip and upload it in Plesk > Extensions > My Extensions > Upload Extension.
  - The module ID must be `inwx` to match `pm_Context::init('inwx')`. If you use a different ID, adjust `pm_Context::init()` and the CLI examples accordingly.
- Alternatively, install by CLI:
  - Linux:
    ```bash
    plesk bin extension --install /path/to/inwx.zip
    ```
  - Windows:
    ```powershell
    "%plesk_dir%\bin\extension.exe" --install C:\path\to\inwx.zip
    ```

### 2) Backend registration

- `post-install.php` automatically registers this script as the Plesk custom DNS backend:
  ```bash
  plesk bin server_dns --enable-custom-backend \
    "/usr/local/psa/bin/extension --exec inwx inwx.php"
  ```
- If you need to re-register manually, you can run the command above yourself (adjust path on Windows).
- To disable and restore Plesk’s built-in DNS, uninstall the extension or run:
  ```bash
  plesk bin server_dns --disable-custom-backend
  ```

## Configuration

You can configure credentials either via environment variables or via the included Plesk UI (Extensions > INWX DNS). Environment variables are still the easiest for non-interactive deployments.

### Environment variables (recommended)

```bash
INWX_LIVE=1            # 1 = live api.domrobot.com, unset/0 = OTE sandbox
INWX_USERNAME=your_user
INWX_PASSWORD=your_pass
INWX_2FA_SECRET=xxxx   # optional TOTP shared secret if your account requires 2FA for API
```

Set these for the Plesk services environment so they are available to extension processes. On Linux you can set them system-wide in `/etc/environment` or via systemd overrides for sw-engine and extension binaries; on Windows set system environment variables and restart Plesk services.

### Plesk settings (advanced)

The script also reads `pm_Settings` keys if present:

- `inwx_username`, `inwx_password`, `inwx_2fa_secret`, `inwx_live` (`"1"` for live)

The included UI (Extensions > INWX DNS) writes these keys for you. For automated setups, you may still prefer environment variables.

## Behavior summary

- No automatic zone creation: the zone must already exist at INWX. If missing, the script logs an error and marks the operation as failed for that zone.
- Incremental record sync: only records that changed are created/updated/deleted. SOA and NS are never changed by this backend.
- Preservation of INWX-only records: records that exist only at INWX and do not conflict with Plesk are kept as-is.
- Conflict resolution: when Plesk and INWX both manage the same “base key” (type|name|prio), Plesk wins; extra INWX records under that base key are removed.
- PTR operations are ignored and only logged.

## How it works

- Plesk calls `inwx.php` with a JSON “change feed” on stdin (see `TUTORIAL.md`). For create/update, the script:
  1. Authenticates to INWX (live or OTE) using environment variables or Plesk settings.
  2. Checks that the target zone exists at INWX via `nameserver.info`. It does not call `nameserver.create`.
  3. Builds the desired record set from Plesk, normalizes fields (name, content, prio, ttl) and filters out SOA/NS.
  4. Fetches current INWX records and computes a minimal diff:
     - Exact matches are skipped.
     - If a desired record matches an existing record by base key (type|name|prio), the existing record is updated to the desired content/ttl.
     - If no existing record matches, a new record is created.
     - After processing desired records, remaining existing records are deleted only if their base key is managed by Plesk in this run; otherwise they are preserved.
- For delete, it removes the zone (`nameserver.delete`).
- PTR commands (`createPTRs`/`deletePTRs`) are logged and skipped, as reverse zones are usually not manageable here.

### Record handling notes

- Supported record types include: A, AAAA, CNAME, MX, TXT, SRV, CAA, PTR, SSHFP, TLSA, NSEC, DNAME, DNSKEY, DS, HINFO, LOC, NAPTR, RP, SPF, URI. SOA and NS are skipped during sync.
- MX: `rr.opt` is used as priority (`prio`).
- SRV: `rr.opt` is parsed as `priority weight port`; `prio` is set from the priority, and content becomes `weight port target` (target from `rr.value`, trailing dot ensured).
- TXT: value is normalized (tabs to spaces) and sent raw (no extra quoting is added beyond what INWX expects).
- CAA: value is quoted; if `rr.opt` contains flags/tag (e.g., `0 issue`), it is prefixed to the content.
- TTL: per-record TTL if present; otherwise falls back to SOA TTL.

### Exit codes & logging

- Logging: the script writes simple tagged lines to stdout/stderr, e.g. `[INFO]`, `[WARN]`, `[ERR]`. Check Plesk logs: `/var/log/plesk/panel.log` (Linux) or `%plesk_dir%\admin\logs\php_error.log` (Windows).
- If INWX credentials are missing or login fails, the script logs a warning and exits 0 (to not block Plesk operations) without applying changes.
- If the INWX zone is missing (auto-creation disabled), the script logs an error and marks the run as failed (overall exit code 255 if any zone failed).

## Manual testing from CLI

Once the extension is installed and the backend is enabled, you can test with a sample JSON:

```bash
cat > /tmp/zone.json <<'JSON'
[
  {
    "command": "update",
    "zone": {
      "name": "example.com.",
      "displayName": "example.com.",
      "soa": {"ttl": 3600, "email": "hostmaster@example.com", "status": 0, "type": "master", "refresh": 10800, "retry": 3600, "expire": 604800, "minimum": 10800, "serial": 1700000000, "serial_format": "UNIXTIMESTAMP"},
      "rr": [
        {"host": "example.com.", "type": "A",   "value": "203.0.113.10"},
        {"host": "www.example.com.", "type": "CNAME", "value": "example.com."},
        {"host": "example.com.", "type": "MX",  "opt": "10", "value": "mail.example.com."},
        {"host": "_acme-challenge.example.com.", "type": "TXT", "value": "some-token"},
        {"host": "example.com.", "type": "CAA", "opt": "0 issue", "value": "letsencrypt.org"}
      ]
    }
  }
]
JSON

plesk bin extension --exec inwx inwx.php < /tmp/zone.json
```

You should see informational logs and, if credentials are correct and the zone already exists at INWX, the records will be synchronized incrementally.

## Uninstall / disable

- Plesk > Extensions > find “INWX DNS” (your installed package) > Uninstall
- Or CLI:
  ```bash
  plesk bin extension --uninstall inwx
  ```
- The pre-uninstall hook disables the custom backend automatically. If needed, run:
  ```bash
  plesk bin server_dns --disable-custom-backend
  ```

## Troubleshooting

- Zone not found at INWX
  - Create the zone manually in your INWX account. This backend will not create zones automatically.
  - Verify the zone name in Plesk exactly matches the INWX zone (no trailing dot in INWX UI; Plesk sends with trailing dot and the backend handles it).
- No effect, credentials/login issue
  - Ensure `INWX_USERNAME`/`INWX_PASSWORD` (or Plesk settings) are set and valid, and that `INWX_LIVE` matches your account context (live vs. OTE). When login fails, the backend intentionally exits 0 after logging a warning.
- Unexpected extra records at INWX
  - By design, INWX-only records that do not conflict with Plesk are preserved. If you need Plesk to manage a given name/type/prio tuple, add the record in Plesk; the backend will then reconcile and remove conflicting INWX-only records under that base key.
- NS records differ
  - Intentional: the backend does not change SOA/NS at INWX.

## Notes & limitations

- Incremental synchronization: only records that are changed are touched; SOA and NS are never modified.
- Non‑conflicting INWX records persist; conflicts are resolved in favor of Plesk for the same base key (type|name|prio).
- No automatic zone creation; zones must pre-exist in INWX.
- PTR commands from Plesk are ignored.

## License

See the [LICENSE](./LICENSE) file for details. Copyright © Lupo GmbH.

## Disclaimer

This software is provided “as is”, without warranty of any kind, express or implied, including but not limited to the warranties of merchantability, fitness for a particular purpose and noninfringement. In no event shall the authors or copyright holders be liable for any claim, damages or other liability, whether in an action of contract, tort or otherwise, arising from, out of or in connection with the software or the use or other dealings in the software.

## Contributing

PRs welcome. Keep changes minimal and aligned with Plesk’s custom backend model. If you add a UI, take the bundled `route53` example as a reference for packaging and metadata.
