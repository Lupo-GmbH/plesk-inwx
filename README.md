Plesk INWX DNS Backend

The path to extension's entry points:           /opt/psa/admin/htdocs/modules/inwx/
The path to PHP classes:                        /opt/psa/admin/plib/modules/inwx/
The path to installation scripts:               /opt/psa/admin/plib/modules/inwx/scripts/
The path to the directory with run-time data:   /opt/psa/var/modules/inwx/


This repository provides a minimal Plesk extension that synchronizes DNS zones from Plesk to INWX nameservers using the official DomRobot API. It implements Plesk’s “custom DNS backend” model: whenever DNS zones change in Plesk, the changes are pushed to INWX.

What you get
- Automatic creation/update/delete of zones on INWX when domains change in Plesk.
- Full zone sync on each update: non-SOA/NS records are purged and recreated to match Plesk.
- Record mapping for common types: A, AAAA, CNAME, MX, TXT, SRV, CAA, …
- Safety defaults: apex NS records are not managed (to avoid conflicts with INWX defaults), PTR ops are ignored.

Requirements
- Plesk Obsidian 18.x or newer on Linux or Windows.
- PHP 7.4–8.3 available on the Plesk host (Plesk ships PHP for extensions).
- An INWX account (live or OTE/sandbox) and API access.

Repository layout
- src/plib/scripts/inwx.php — the core synchronizer invoked by Plesk.
- src/plib/scripts/post-install.php — registers the custom DNS backend on install.
- src/plib/scripts/pre-uninstall.php — unregisters the backend on uninstall.
- src/plib/vendor — Composer vendor directory (autoloader + DomRobot client).
- TUTORIAL.md — Plesk’s specification for custom DNS backends.
- INWX-API.md — extracted reference for the INWX DomRobot API.

Installation
1) Build and install the extension
- Package the extension directory (src) into a zip and upload it in Plesk > Extensions > My Extensions > Upload Extension.
  - The module ID must be inwx to match pm_Context::init('inwx'). If you use a different ID, adjust pm_Context::init() and the CLI examples accordingly.
- Alternatively, install by CLI:
  - Linux: plesk bin extension --install /path/to/inwx.zip
  - Windows: "%plesk_dir%\bin\extension.exe" --install C:\path\to\inwx.zip

2) Backend registration
- post-install.php automatically registers this script as the Plesk custom DNS backend:
  plesk bin server_dns --enable-custom-backend \
    "/usr/local/psa/bin/extension --exec inwx inwx.php"
- If you need to re-register manually, you can run the command above yourself (adjust path on Windows).
- To disable and restore Plesk’s built-in DNS, uninstall the extension or run:
  plesk bin server_dns --disable-custom-backend

Configuration
You can configure credentials either via environment variables or Plesk settings. This minimal extension ships no UI, so environment variables are the easiest.

Environment variables (recommended)
- INWX_LIVE=1            # 1 = live api.domrobot.com, unset/0 = OTE sandbox
- INWX_USERNAME=your_user
- INWX_PASSWORD=your_pass
- INWX_2FA_SECRET=xxxx   # optional TOTP shared secret if your account requires 2FA for API

Set these for the Plesk services environment so they are available to extension processes. On Linux you can set them system-wide in /etc/environment or via systemd overrides for sw-engine and extension binaries; on Windows set system environment variables and restart Plesk services.

Plesk settings (advanced)
The script also reads pm_Settings keys if present:
- inwx_username, inwx_password, inwx_2fa_secret, inwx_live ("1" for live)
If you maintain a small admin helper script or UI, set these keys there; otherwise prefer environment variables.

How it works
- Plesk calls inwx.php with a JSON “change feed” on stdin (see TUTORIAL.md). For create/update, the script:
  1) Ensures the zone exists at INWX (nameserver.create/info)
  2) Purges all non-SOA/NS records
  3) Recreates all records from Plesk, honoring TTL and priorities (MX/SRV)
- For delete, it removes the zone (nameserver.delete)
- PTR commands (createPTRs/deletePTRs) are logged and skipped, as reverse zones are usually not manageable here.
- NS records are not recreated to avoid fighting INWX’s default NS set.

Record handling notes
- MX: rr.opt is used as prio
- SRV: rr.opt used as prio, rr.value is passed as content (weight port target)
- TXT: value is normalized (tabs to spaces) and sent raw (INWX does not need wrap quotes)
- CAA: value is sent quoted; if rr.opt contains flags/tag (e.g., "0 issue"), it is prefixed
- TTL: per-record TTL if present; otherwise falls back to SOA TTL

Manual testing from CLI
Once the extension is installed and the backend is enabled, you can test with a sample JSON:

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

You should see informational logs and, if credentials are correct, the zone and records will be created/updated at INWX.

Uninstall / disable
- Plesk > Extensions > find “INWX DNS” (your installed package) > Uninstall
- Or CLI: plesk bin extension --uninstall inwx
- The pre-uninstall hook disables the custom backend automatically. If needed, run:
  plesk bin server_dns --disable-custom-backend

Troubleshooting
- No effect, zone not created on INWX
  - Ensure credentials are set and valid (INWX_USERNAME/INWX_PASSWORD or pm_Settings), and check if INWX_LIVE matches your account context (live vs. OTE).
  - Review Plesk logs: /var/log/plesk/panel.log (Linux) or %plesk_dir%\admin\logs\php_error.log (Windows). The script logs to stdout/stderr with simple tags like [INFO] and [ERR].
- Authentication errors
  - If your account enforces 2FA for API, add INWX_2FA_SECRET (TOTP shared secret) or disable API-2FA at INWX.
- Duplicate/extra records remain
  - This backend purges all non-SOA/NS records and recreates them. If you see leftovers, verify that the zone name in Plesk exactly matches the INWX zone and that there are no managed sub-zones.
- NS records are different
  - Intentional: We skip managing NS to avoid fighting the provider’s defaults.

Notes & limitations
- The implementation favors correctness over minimal deltas; it performs purge-and-recreate on each update.
- PTR commands from Plesk are ignored.
- There is no admin UI yet; use environment variables or wire in pm_Settings via your own helper.

License
- Copyright © Lupo GmbH. See composer.json for license notice.

Contributing
- PRs welcome. Keep changes minimal and aligned with Plesk’s custom backend model. If you add a UI, take the bundled route53 example as a reference for packaging and metadata.
