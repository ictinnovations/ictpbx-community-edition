# ICTPBX Community Edition

ICTPBX is an open-source unified communications management platform combining full-featured IP-PBX and Fax server capabilities in a single web UI. It is built on top of [ICTFax](https://www.ictfax.com) Angular Framework, [ICTCore](https://github.com/ictinnovations/ictcore) (PHP REST framework), [FusionPBX](https://www.fusionpbx.com/) (PBX configuration engine), and [FreeSWITCH](https://freeswitch.com/) (media server).

### PBX Features
- SIP extension and device management
- IVR auto-attendant with nested menus
- ACD call queues with agent assignment
- Voicemail boxes with email delivery
- Conference rooms
- Ring groups and follow-me forwarding
- Time-based call routing and call flows
- Call blocking (inbound and outbound)
- SIP trunk / gateway management
- Inbound DID routing
- Real-time active call monitoring
- Call Detail Records (CDR) with search and export

### Fax Features
- Send fax from web UI (upload document via SIP trunk)
- Receive inbound fax via DID (T.38 / audio fallback)
- Fax-to-email delivery — auto-forward received faxes to one or more email addresses
- Multi-recipient fax (department fax — all extensions linked to a DID receive the email)
- Fax campaign management (bulk send)
- Fax CDR and transmission history

### Community Edition vs Service Provider Edition (SP Edition)
The **Community Edition** (this repo) is free, single-tenant, and licensed under [MPL 2.0](https://mozilla.org/MPL/2.0/).
The **Service Provider Edition (SP Edition)** adds multi-tenant management, white-label branding, and usage billing — visit [www.ictpbx.com](https://www.ictpbx.com) for details.

---

**Project site:** [www.ictpbx.com](https://www.ictpbx.com)


---

## Architecture Overview

```
Angular Frontend (CE build)
        │
        │  HTTP/REST  (/api)
        ▼
ICTCore PHP REST API  (/usr/ictcore)   ← [edition] mode = community
        │                    │
        │ MariaDB (ictfax)   │ PostgreSQL (fusionpbx)
        ▼                    ▼
   ICTCore tables       FusionPBX tables (v_*)
                              │
                              ▼
                     FusionPBX hooks → FreeSWITCH XML reload
                              │
                              ▼
                     FreeSWITCH 1.10.12
```

---

## Server Requirements

| Component       | Version           | Notes                        |
|-----------------|-------------------|------------------------------|
| OS              | Rocky Linux 8+    |                              |
| Apache          | 2.4.62            | mod_rewrite, mod_ssl enabled |
| PHP             | 8.3.x             | Extensions: pdo, pdo_mysql, pgsql, imagick, mbstring, json, openssl |
| MariaDB         | 10.11.x           | Database: `ictfax`           |
| PostgreSQL      | 16.x              | Database: `fusionpbx`        |
| FusionPBX       | 5.5.7             | At `/var/www/fusionpbx`      |
| FreeSWITCH      | 1.10.12           | Managed by FusionPBX         |
| Memcached       | 1.6.x             | Port 11211 (session cache)   |
| Node.js      | 18.x LTS (recommended)              | Frontend build only          |

---

## Directory Layout

```
/usr/ictcore/               ← ICTCore root (owned by user: ictcore)
├── core/                   ← PHP domain classes (Extension, Gateway, etc.)
│   └── Api/                ← REST API endpoint classes (*Api.php)
├── wwwroot/                ← Apache DocumentRoot for /api
├── etc/
│   ├── ictcore.conf        ← Main config (DB, JWT, provisioning, edition)
│   ├── ssh/                ← JWT RS256 keypair (NEVER commit ib_node.pem)
│   └── http/               ← Apache vhost configs (archived copies)
├── db/                     ← SQL migration scripts + schema snapshots
├── patches/
│   └── fusionpbx-local.patch  ← Local diff vs upstream FusionPBX
├── cache/                  ← Route cache (rm -f cache/* after API changes)
└── bin/                    ← CLI utilities

/var/www/fusionpbx/         ← FusionPBX 5.5.7 (owned by: apache)
/etc/freeswitch/            ← FreeSWITCH config
/etc/httpd/conf.d/          ← Apache vhosts
```

---

## Installation

### Quick Install — Full Stack (recommended)

One command installs backend **and** frontend end-to-end (backend + FusionPBX + FreeSWITCH + Angular UI):

```bash
bash <(curl -fsSL https://raw.githubusercontent.com/ictinnovations/ictpbx-community-edition-gui/main/install-ce.sh)
```

With domain + HTTPS (Let's Encrypt):

```bash
DOMAIN=pbx.example.com TLS_EMAIL=admin@example.com \
    bash <(curl -fsSL https://raw.githubusercontent.com/ictinnovations/ictpbx-community-edition-gui/main/install-ce.sh)
```

The unified installer collects all passwords once, then runs `ictcore-ce-install.sh` (backend) followed by `ictpbx-ce-install.sh` (frontend) automatically.

For non-interactive / CI runs:

```bash
INSTALLER_AUTO=1 \
  MARIADB_ROOT_PASS=xxx MARIADB_ICTFAX_PASS=xxx \
  PG_FUSIONPBX_PASS=xxx ICTCORE_ADMIN_PASS=xxx \
  bash <(curl -fsSL https://raw.githubusercontent.com/ictinnovations/ictpbx-community-edition-gui/main/install-ce.sh)
```

### Partial Install / Upgrade

Backend only (or upgrading an existing node):

```bash
dnf install -y git
git clone https://github.com/ictinnovations/ictpbx-community-edition.git /usr/ictcore
bash /usr/ictcore/ictcore-ce-install.sh
```

> `dnf install -y git` is required because Rocky Linux minimal images do not ship `git` preinstalled.

The CE installer handles everything end-to-end:

- System packages, EPEL, Remi, PowerTools/CRB
- Apache 2.4 (vhost + mod_rewrite + SELinux booleans)
- PHP 8.3 (Remi)
- Memcached
- MariaDB 10.11 (`ictfax` database, `ictfax` user)
- PostgreSQL 16 (`fusionpbx` database)
- FusionPBX 5.5.7 (`v5.5.7` tag) + local patches
- FreeSWITCH 1.10.12
- ICTCore checkout, Composer dependencies, schema load
- JWT RS256 keypair (`ib_node`, `ib_node.pub`, `ib_node.crt`, `ib_node.pem`)
- `ictcore.conf` generation **with `[edition] mode = community`**
- Firewall rules (http, https)
- Initial admin password set on `admin@ictcore.org`

The installer prompts for four passwords up front (MariaDB root, MariaDB ictfax user, PostgreSQL fusionpbx, ICTCore admin) and runs unattended.

### Schema files loaded (CE)

The installer loads these in order — `branding.sql` is intentionally omitted because branding is a Service Provider (SP) Edition-only feature:

```
database.sql
tenant.sql
voice.sql
fax.sql
sms.sql
password_policy.sql
contact_dnc.sql
pbx_quota_extensions.sql
```

### `ictcore.conf` (CE)

The installer generates `/usr/ictcore/etc/ictcore.conf` with the standard sections plus an `[edition]` block at the end:

```ini
[website]
host  = your-domain.com
port  = 80
path  = /api/
url   = http://your-domain.com/api

[security]
hash_type    = RS256
token_expiry = 31104000
private_key  = /usr/ictcore/etc/ssh/ib_node
public_key   = /usr/ictcore/etc/ssh/ib_node.pub

[db]
host = localhost
port = 3306
user = ictfax
name = ictfax
type = mysql
pass = <DB_PASS>

[freeswitch]
user     = user
password = <auto-rotated by installer � saved at end of install>
host     = 127.0.0.1
port     = 8021

[edition]
mode = community
```

When `[edition] mode = community` is set, the backend:

- Returns 404 on all branding endpoints
- Bypasses user daily/monthly cap enforcement
- Forces `is_admin=1` on every authenticated JWT
- Treats the system as single-tenant

### JWT keypair files

| File          | Purpose                                      | gitignored |
|---------------|----------------------------------------------|------------|
| `ib_node`     | RSA private key — JWT signing                | Yes        |
| `ib_node.pub` | RSA public key — JWT verification            | Yes        |
| `ib_node.crt` | Self-signed X.509 cert — WSS/TLS             | Yes        |
| `ib_node.pem` | PEM copy of private key — Apache/FreeSWITCH  | Yes        |

> **IMPORTANT:** All four files are in `.gitignore` and must never be committed. They must be regenerated on every fresh server installation. The CE installer handles this automatically.

### Clear route cache

After uploading or modifying any `*Api.php` file:

```bash
rm -f /usr/ictcore/cache/*
```

---

## ICTCore API Reference

| Item              | Value                                          |
|-------------------|------------------------------------------------|
| Base URL          | `http://<host>/api/<resource>`                 |
| Auth              | `Authorization: Bearer <JWT>`                  |
| JWT algorithm     | RS256, 1-year expiry                           |
| Default admin     | `admin@ictcore.org`                            |
| Permission model  | `usr.user_permission` — delimited string (CE forces admin)   |

### Authentication

```bash
curl -X POST http://<host>/api/authenticate \
  -H "Content-Type: application/json" \
  -d '{"username":"admin@ictcore.org","password":"<pass>"}'
```

Returns a JWT. Include as `Authorization: Bearer <token>` on all subsequent requests.

### PBX Modules (FusionPBX-backed)

All PBX endpoints write directly to FusionPBX PostgreSQL. FusionPBX hooks auto-reload FreeSWITCH XML.

| Endpoint               | Backing Table(s)                              |
|------------------------|-----------------------------------------------|
| `/api/fpbx_extension`  | `v_extensions`                                |
| `/api/device`          | `v_devices`                                   |
| `/api/ring_group`      | `v_ring_groups`                               |
| `/api/call_queue`      | `v_call_center_queues`, `v_call_center_tiers` |
| `/api/ivr_menu`        | `v_ivr_menus`, `v_ivr_menu_options`           |
| `/api/voicemail`       | `v_voicemails`                                |
| `/api/conference`      | `v_conference_centers`                        |
| `/api/time_condition`  | `v_dialplans` (app_uuid `4b821450-...`)       |
| `/api/call_flow`       | `v_call_flows` + companion dialplan           |
| `/api/call_block`      | `v_call_block`                                |
| `/api/follow_me`       | `v_follow_me`, `v_follow_me_destinations`     |
| `/api/music_on_hold`   | `v_music_on_hold`                             |
| `/api/gateway`         | `v_gateways`                                  |
| `/api/inbound_route`   | `v_destinations`, `v_dialplans`               |
| `/api/realtime`        | FreeSWITCH ESL (fs_cli polling)               |

### EE-only endpoints (return 404 in CE)

- `/api/branding/*` — all branding CRUD
- `/api/tenant` — tenant CRUD (single-tenant in CE)

## FusionPBX Integration

ICTPBX uses FusionPBX as a **backend configuration store only**:

- **PostgreSQL database** (`fusionpbx` DB, `v_*` tables) — PBX objects (extensions, devices, ring groups, IVR menus, call queues, voicemails, gateways, dialplans, etc.) are read and written directly via PDO.
- **FusionPBX XML hooks** — when database records change, FusionPBX's internal hooks automatically regenerate and reload FreeSWITCH XML configuration.
- **No FusionPBX UI** — the FusionPBX web interface is not used, not bundled, and not exposed. All management is done through the ICTPBX Angular UI via ICTCore REST API.
- **No FusionPBX PHP code** — no FusionPBX classes or libraries are imported or executed by ICTCore.

FusionPBX is licensed under the [Mozilla Public License 2.0](https://www.mozilla.org/en-US/MPL/2.0/).

---

### Databases

**MariaDB `ictfax`** — ICTCore data (users, CDR, contacts, messaging)

```bash
mysql -u ictfax -p ictfax
```

**PostgreSQL `fusionpbx`** — FusionPBX PBX config (all tables prefixed `v_`)

```bash
PGPASSWORD="<FPBX_PG_PASS>" psql -h 127.0.0.1 -U fusionpbx -d fusionpbx
```

> Always use `-h 127.0.0.1` — `pg_hba.conf` requires TCP for md5 auth.

---

## Role / Permission Model (CE)

- **Role 2** = Super Admin (`is_admin=1`)
- **Role 1** = End User (limited permissions)

In CE, every authenticated user is treated as admin (role 3 / Tenant Admin tier does not apply because there is only one tenant). Permissions are stored as a delimited string in `usr.user_permission` for end-user accounts.

---

## Frontend Configuration

Repository: [`ictinnovations/ictpbx-community-edition-gui`](https://github.com/ictinnovations/ictpbx-community-edition-gui)

### Requirements

| Tool        | Version   |
|-------------|-----------|
| Node.js      | 18.x LTS (recommended)      |
| npm         | 6.x       |
| Angular CLI | ^13       |

### Setup

> The **Quick Install** command above handles this automatically.
> Run the steps below only if you installed the backend separately.

```bash
dnf install -y git
git clone https://github.com/ictinnovations/ictpbx-community-edition-gui.git /usr/ictpbxx
bash /usr/ictpbxx/ictpbx-ce-install.sh
```

### Manual / dev setup

```bash
git clone https://github.com/ictinnovations/ictpbx-community-edition-gui.git ictpbx
cd ictpbx
npm install --legacy-peer-deps
bash ng-serve          # runs: ng serve --port 4201 --proxy-config proxy.conf.json
```

App: **http://localhost:4201**

### Production build (CE)

```bash
ng build --configuration=community
# Deploy dist/ to your web server
```

---

## Maintenance

### After any API class change
```bash
rm -f /usr/ictcore/cache/*
systemctl reload httpd
```

### Check service status
```bash
systemctl status httpd mariadb postgresql freeswitch memcached
```

### FreeSWITCH CLI
```bash
fs_cli
fs_cli -x "status"
fs_cli -x "show channels"
```

### FusionPBX XML reload
```bash
fs_cli -x "reloadxml"
```

---

## Security Notes

- `ib_node.pem` (JWT private key) must **never** be committed to git — it is in `.gitignore`
- Rotate the JWT keypair if the server is reprovisioned or the key is exposed
- PostgreSQL uses md5 auth over TCP — always connect via `-h 127.0.0.1`
- Apache runs as user `ictcore`; FusionPBX files owned by `apache`
- FreeSWITCH ESL (port 8021) is localhost-only — do not expose externally

---

**Project site:** [www.ictpbx.com](https://www.ictpbx.com)  
Developed by [ICT Innovations](https://www.ictinnovations.com)
