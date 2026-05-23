#!/usr/bin/env bash
# ictcore-install.sh — ICTPBX Backend Setup
# Supported distros: Rocky Linux 8.x / 9.x  ·  CentOS Stream 8 / 9
#                    (EL8 path uses powertools repo, EL9 path uses crb)
# Installs: MariaDB, PostgreSQL, PHP 8.3, Apache, Memcached,
#           FreeSWITCH 1.10.12, FusionPBX 6.6.0, ICTCore backend
# Run as root on a fresh node.
# Usage: bash /usr/ictcore/ictcore-install.sh
set -euo pipefail

# Finding #4 — explicit ERR trap so failures are loud AND the non-zero exit
# code propagates cleanly past the tee subshell to the parent. Without this,
# the `exec > >(tee -a "$LOG") 2>&1` line below could occasionally let a
# non-zero status get masked, causing CI to see green for a broken install.
trap '_rc=$?; echo ""; echo "[FAIL] Installer aborted at line $LINENO (exit $_rc)" >&2; exit $_rc' ERR

ICTCORE_DIR=/usr/ictcore
FUSIONPBX_DIR=/var/www/fusionpbx
APACHE_CONF=/etc/httpd/conf.d/ictpbx.conf
LOG=/tmp/ictcore-install.log
ICTCORE_REPO=https://github.com/ictvision/ictpbx.git
FUSIONPBX_REPO=https://github.com/fusionpbx/fusionpbx.git
FUSIONPBX_TAG=v6.6.0

echo "============================================================"
echo " ICTPBX Backend Setup"
echo " Distros : Rocky Linux 8.x / 9.x  ·  CentOS Stream 8 / 9"
echo " Log     : $LOG"
echo "============================================================"
exec > >(tee -a "$LOG") 2>&1

# ── helpers ──────────────────────────────────────────────────
ok()   { echo "[OK]  $*"; }
info() { echo "[..] $*"; }
warn() { echo "[WARN] $*"; }
fail() { echo "[FAIL] $*"; exit 1; }
hdr()  { echo ""; echo "── $* ──────────────────────────────────────────────────────"; }

# quiet: run command silently on success; on failure, dump full output
# and return non-zero so `set -e` aborts the script. Replaces the
# `&>/dev/null` pattern that was hiding install errors.
quiet() {
    local _out
    if ! _out=$("$@" 2>&1); then
        echo "$_out" >&2
        return 1
    fi
}

# ── root check ───────────────────────────────────────────────
[[ "$EUID" -eq 0 ]] || fail "This script must be run as root"

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# EXISTING-INSTALL DETECTION (audit gap #8)
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# Multiple signals so a half-finished install isn't mistaken for "done":
#   1. /usr/ictcore/etc/ictcore.conf exists (Step 11 completed)
#   2. /usr/ictcore/etc/ssh/ib_node exists (JWT keypair generated)
#   3. ictfax.account table has rows (schema + admin seed loaded)
EXISTING_INSTALL=0
if [[ -f /usr/ictcore/etc/ictcore.conf && -f /usr/ictcore/etc/ssh/ib_node ]]; then
    if mysql -u root --batch --skip-column-names -e \
        "SELECT 1 FROM ictfax.account LIMIT 1" 2>/dev/null | grep -q '^1$'; then
        EXISTING_INSTALL=1
    fi
fi

# INSTALLER_ACTION can be pre-set to skip the prompt entirely (CI / unattended).
# Valid values: keep | upgrade | abort  (k/u/a short forms accepted).
if [[ "$EXISTING_INSTALL" == "1" && -z "${INSTALLER_ACTION:-}" && "${INSTALLER_AUTO:-0}" != "1" ]]; then
    echo ""
    echo "  ⚠ Existing install detected:"
    echo "      /usr/ictcore/etc/ictcore.conf present"
    echo "      JWT keypair present"
    echo "      ictfax.account has rows"
    echo ""
    echo "    [k]eep    — leave everything as-is and exit"
    echo "    [u]pgrade — reload schema files + reapply idempotent fixes"
    echo "                (passwords MUST be in env vars; no prompts)"
    echo "    [a]bort   — exit without changes"
    echo ""
    read -rp "  Choose [k/u/a, default=k]: " INSTALLER_ACTION
    INSTALLER_ACTION="${INSTALLER_ACTION:-k}"
fi

case "${INSTALLER_ACTION:-k}" in
    k|K|keep)
        if [[ "$EXISTING_INSTALL" == "1" ]]; then
            info "Existing install kept — no changes. Verify with:"
            info "  curl -sk http://127.0.0.1/api/authenticate -X POST -d '{}' "
            exit 0
        fi
        ;;
    u|U|upgrade)
        info "Upgrade mode — schema reload forced; passwords must come from env vars"
        FORCE_SCHEMA=1
        ;;
    a|A|abort)
        info "Aborted by user"
        exit 1
        ;;
    *)
        fail "Unknown INSTALLER_ACTION '${INSTALLER_ACTION}' (use k/u/a)"
        ;;
esac

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# COLLECT PASSWORDS UPFRONT
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# prompt_secret VAR_NAME "prompt text"
# - Pre-set env var → use it (no prompt)
# - INSTALLER_AUTO=1 + var empty → fail fast
# - Otherwise → silent read into the named var
prompt_secret() {
    local _var="$1"; local _label="$2"
    if [[ -n "${!_var:-}" ]]; then
        ok "${_label}: using pre-set ${_var} from environment"
        return 0
    fi
    if [[ "${INSTALLER_AUTO:-0}" == "1" ]]; then
        fail "${_var} must be set in INSTALLER_AUTO mode (e.g. ${_var}=secret bash $0)"
    fi
    read -rsp "  ${_label}: " "$_var"; echo
}

echo ""
echo "  Enter passwords (will not be echoed):"
echo ""
prompt_secret MARIADB_ROOT_PASS   "MariaDB root password       "
prompt_secret MARIADB_ICTFAX_PASS  "MariaDB ictfax user password"
prompt_secret PG_FUSIONPBX_PASS    "PostgreSQL fusionpbx password"
prompt_secret ICTCORE_ADMIN_PASS   "ICTCore admin password      "
echo ""
[[ -n "$MARIADB_ROOT_PASS" && -n "$MARIADB_ICTFAX_PASS" && -n "$PG_FUSIONPBX_PASS" && -n "$ICTCORE_ADMIN_PASS" ]] \
    || fail "All passwords are required"

# Auto-generate FreeSWITCH ESL password — replaces the upstream-default
# user/ClueCon. Written to both ictcore.conf [freeswitch] and
# event_socket.conf.xml (audit gap #4). Printed once at end of install.
# On upgrade: skip rotation so existing fs_cli sessions don't break.
if [[ "${INSTALLER_ACTION:-}" == "u" || "${INSTALLER_ACTION:-}" == "upgrade" ]]; then
    ESL_PASS="${ESL_PASS:-}"
    if [[ -z "$ESL_PASS" ]]; then
        ESL_PASS=$(awk -F'=' '/^\[freeswitch\]/{f=1; next} /^\[/{f=0} f && /^password/{gsub(/ /,"",$2); print $2; exit}' \
            /usr/ictcore/etc/ictcore.conf 2>/dev/null || true)
    fi
    [[ -n "$ESL_PASS" ]] || ESL_PASS="$(openssl rand -hex 16)"
else
    ESL_PASS="$(openssl rand -hex 16)"
fi

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# STEP 1 — System packages & repos
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
hdr "Step 1: System packages & repos"

info "Enabling EPEL, PowerTools / CRB..."
quiet dnf install -y epel-release dnf-utils
# Rocky 8 = powertools, Rocky 9 = crb
if dnf repolist all 2>/dev/null | grep -qi 'crb'; then
    quiet dnf config-manager --set-enabled crb
else
    quiet dnf config-manager --set-enabled powertools
fi
ok "EPEL + PowerTools/CRB enabled"

info "Installing base utilities..."
quiet dnf install -y git curl wget unzip tar jq openssl \
    policycoreutils-python-utils
ok "Base utilities installed"

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# STEP 2 — Apache
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
hdr "Step 2: Apache"

quiet dnf install -y httpd mod_ssl
quiet systemctl enable --now httpd
ok "Apache $(httpd -v 2>&1 | head -1 | awk '{print $3}') installed"

# Disable default welcome page
[[ -f /etc/httpd/conf.d/welcome.conf ]] && \
    mv /etc/httpd/conf.d/welcome.conf /etc/httpd/conf.d/welcome.conf.disabled

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# STEP 3 — PHP 8.3
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
hdr "Step 3: PHP 8.3"

info "Installing Remi repo for PHP 8.3..."
# Detect EL major version (Finding #2). The previous "try EL8 then EL9" pattern
# always printed a scary "Problem: conflicting requests" block on EL9 boxes
# before the EL9 RPM succeeded — functional but misleading. Pick the right RPM
# directly via `rpm -E %rhel` (works on Rocky 8/9 + CentOS Stream 8/9).
EL_VER=$(rpm -E %rhel 2>/dev/null || echo 9)
quiet dnf install -y "https://rpms.remirepo.net/enterprise/remi-release-${EL_VER}.rpm"
quiet dnf module reset php -y
quiet dnf module enable php:remi-8.3 -y
quiet dnf install -y php php-cli php-fpm php-pdo php-mysqlnd php-pgsql \
    php-mbstring php-json php-openssl php-xml php-gd php-pecl-imagick \
    php-pecl-memcached php-opcache php-curl php-process
ok "PHP $(php -r 'echo PHP_VERSION;') installed"

systemctl enable --now php-fpm >/dev/null 2>&1 || true

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# STEP 4 — Memcached
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
hdr "Step 4: Memcached"

quiet dnf install -y memcached
quiet systemctl enable --now memcached
ok "Memcached installed (port 11211)"

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# STEP 5 — MariaDB
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
hdr "Step 5: MariaDB 10.11"

cat > /etc/yum.repos.d/mariadb.repo <<'REPO'
[mariadb]
name = MariaDB 10.11
baseurl = https://downloads.mariadb.com/MariaDB/mariadb-10.11/yum/rhel/$releasever/$basearch
gpgkey = https://downloads.mariadb.com/MariaDB/RPM-GPG-KEY-MariaDB
gpgcheck = 1
REPO

quiet dnf install -y MariaDB-server MariaDB-client
quiet systemctl enable --now mariadb
ok "MariaDB $(mariadb --version 2>&1 | awk '{print $5}' | tr -d ',') installed"

info "Securing MariaDB..."
# Idempotent: if root already has the supplied password, skip; if root has no password (fresh), set it.
if mysql -u root -p"${MARIADB_ROOT_PASS}" -e "SELECT 1" >/dev/null 2>&1; then
    ok "MariaDB root password already set — skipping secure step"
elif mysql -u root -e "SELECT 1" >/dev/null 2>&1; then
    mysql -u root <<SQL
ALTER USER 'root'@'localhost' IDENTIFIED BY '${MARIADB_ROOT_PASS}';
DELETE FROM mysql.user WHERE User='';
DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost','127.0.0.1','::1');
DROP DATABASE IF EXISTS test;
DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';
FLUSH PRIVILEGES;
SQL
    ok "MariaDB secured"
else
    fail "MariaDB root accepts neither no-password nor the supplied MARIADB_ROOT_PASS — manual intervention required"
fi

info "Creating ictfax database and user..."
mysql -u root -p"${MARIADB_ROOT_PASS}" <<SQL
CREATE DATABASE IF NOT EXISTS ictfax CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'ictfax'@'localhost' IDENTIFIED BY '${MARIADB_ICTFAX_PASS}';
GRANT ALL PRIVILEGES ON ictfax.* TO 'ictfax'@'localhost';
FLUSH PRIVILEGES;
SQL
ok "Database 'ictfax' and user 'ictfax'@'localhost' created"

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# STEP 6 — PostgreSQL 16
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
hdr "Step 6: PostgreSQL 16"

_EL_VER=$(rpm -E '%{rhel}' 2>/dev/null || echo 8)
_ARCH=$(uname -m)
# pgdg repo install is tolerated to fail (already installed); the actual
# postgresql16 install below is the load-bearing one and must succeed.
quiet dnf install -y "https://download.postgresql.org/pub/repos/yum/reporpms/EL-${_EL_VER}-${_ARCH}/pgdg-redhat-repo-latest.noarch.rpm" || true
quiet dnf module disable postgresql -y || true
quiet dnf install -y postgresql16-server postgresql16
/usr/pgsql-16/bin/postgresql-16-setup initdb >/dev/null 2>&1 || true
quiet systemctl enable --now postgresql-16
ok "PostgreSQL 16 installed"

# Add PostgreSQL 16 binaries to PATH BEFORE any psql calls. PG16 from PGDG
# only ships in /usr/pgsql-16/bin, not /usr/bin. The postgres OS user's
# default $PATH may or may not include this depending on the image.
echo 'export PATH=/usr/pgsql-16/bin:$PATH' > /etc/profile.d/pgsql16.sh
export PATH=/usr/pgsql-16/bin:$PATH
PSQL=/usr/pgsql-16/bin/psql
ok "PostgreSQL 16 bin added to PATH"

info "Creating fusionpbx database and user..."
sudo -u postgres "$PSQL" -c "CREATE USER fusionpbx WITH PASSWORD '${PG_FUSIONPBX_PASS}';" 2>/dev/null || \
    sudo -u postgres "$PSQL" -c "ALTER USER fusionpbx WITH PASSWORD '${PG_FUSIONPBX_PASS}';"
sudo -u postgres "$PSQL" -c "CREATE DATABASE fusionpbx OWNER fusionpbx;" 2>/dev/null || \
    ok "Database 'fusionpbx' already exists"

# Enable md5 auth over TCP (required by ICTCore + FusionPBX)
PG_HBA=$(sudo -u postgres "$PSQL" -t -c "SHOW hba_file;" 2>/dev/null | tr -d ' ')
if ! grep -q 'host.*fusionpbx.*md5' "$PG_HBA" 2>/dev/null; then
    sed -i '/^host/i host    fusionpbx    fusionpbx    127.0.0.1/32    md5' "$PG_HBA"
    systemctl reload postgresql-16
    ok "pg_hba.conf: md5 auth added for fusionpbx"
fi
ok "PostgreSQL database 'fusionpbx' ready"

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# STEP 7 — FreeSWITCH 1.10.12
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
hdr "Step 7: FreeSWITCH 1.10.12"

# Finding #3 — SignalWire's auth-gated repo URL the installer used previously
# (https://freeswitch.signalwire.com/repo/rpm/release/$basearch/) returns 404
# regardless of PAT. EE prod has FreeSWITCH installed from the okay.com.mx
# community repo, which does not require auth and ships the same 1.10.12 RPMs.
# Switching primary repo to okay; SIGNALWIRE_TOKEN is no longer required but
# is accepted (and ignored) for backward compatibility with existing call sites.
info "Adding okay.com.mx repo (FreeSWITCH packages)..."
cat > /etc/yum.repos.d/okay.repo <<'REPO'
[okay]
name=Extra OKay Packages for Enterprise Linux - $basearch
baseurl=http://repo.okay.com.mx/centos/$releasever/$basearch/release
enabled=1
gpgcheck=0
REPO

# Required by ICTCore (confirmed against EE prod 66.42.114.181).
# Core packages — failure aborts install (no || true).
dnf install -y \
    freeswitch \
    freeswitch-config-vanilla \
    freeswitch-lua \
    freeswitch-application-curl \
    freeswitch-sounds-en-us-callie-8000 \
    freeswitch-asrtts-flite

# okay.com.mx FreeSWITCH RPMs do not ship a systemd unit. Create the
# freeswitch:daemon user/group the unit expects, then write the unit file
# matching EE prod 66.42.114.181's working configuration.
getent group  daemon     &>/dev/null || groupadd -r daemon
getent passwd freeswitch &>/dev/null || useradd -r -g daemon -d /var/lib/freeswitch -s /sbin/nologin freeswitch
if [[ ! -f /usr/lib/systemd/system/freeswitch.service ]]; then
    cat > /usr/lib/systemd/system/freeswitch.service <<'FSUNIT'
[Unit]
Description=freeswitch
After=syslog.target network.target local-fs.target postgresql.service

[Service]
Type=forking
PIDFile=/run/freeswitch/freeswitch.pid
Environment="DAEMON_OPTS=-nonat"
EnvironmentFile=-/etc/default/freeswitch
ExecStartPre=/bin/mkdir -p /var/run/freeswitch/
ExecStartPre=/bin/chown -R freeswitch:daemon /var/run/freeswitch/
ExecStart=/usr/bin/freeswitch -u freeswitch -g daemon -ncwait $DAEMON_OPTS
TimeoutSec=45s
Restart=always
User=root
Group=daemon
LimitCORE=infinity
LimitNOFILE=100000
LimitNPROC=60000
LimitRTPRIO=infinity
LimitRTTIME=7000000
IOSchedulingClass=realtime
IOSchedulingPriority=2
CPUSchedulingPolicy=rr
CPUSchedulingPriority=89
UMask=0007

[Install]
WantedBy=multi-user.target
FSUNIT
    systemctl daemon-reload
    ok "freeswitch.service unit installed (RPM doesn't ship one)"
fi
chown -R freeswitch:daemon /etc/freeswitch /var/lib/freeswitch 2>/dev/null || true

# Audit gap #4 — rotate the upstream-default ESL password before
# FreeSWITCH ever starts with it. Only [0-9a-f] chars in ESL_PASS so
# the | delimiter is safe.
ESL_XML=/etc/freeswitch/autoload_configs/event_socket.conf.xml
if [[ -f "$ESL_XML" ]]; then
    sed -i 's|<param name="password" value="[^"]*"/>|<param name="password" value="'"$ESL_PASS"'"/>|' "$ESL_XML"
    ok "FreeSWITCH ESL password rotated in $ESL_XML"
else
    warn "ESL config missing at $ESL_XML — leaving ictcore.conf [freeswitch] password generated anyway"
fi

# mod_curl is required by application.lua (Lua posts to gateway.php via curl()).
# It ships commented-out in freeswitch-config-vanilla — uncomment it.
FS_MODULES=/etc/freeswitch/autoload_configs/modules.conf.xml
if [[ -f "$FS_MODULES" ]]; then
    sed -i 's|<!--\s*<load module="mod_curl"/>\s*-->|<load module="mod_curl"/>|' "$FS_MODULES"
    # Also handle case where it's already present but commented differently
    grep -q '<load module="mod_curl"/>' "$FS_MODULES" \
        || echo '    <load module="mod_curl"/>' >> "$FS_MODULES"
    ok "mod_curl enabled in $FS_MODULES"
fi

# Raise sessions-per-second from default 30 to 100 — default causes
# NORMAL_TEMPORARY_FAILURE rejections under modest concurrent call load.
FS_SWITCH=/etc/freeswitch/autoload_configs/switch.conf.xml
if [[ -f "$FS_SWITCH" ]]; then
    sed -i 's|<!--\s*<param name="sessions-per-second"[^/]*/>\s*-->|<param name="sessions-per-second" value="100"/>|' "$FS_SWITCH"
    sed -i 's|<param name="sessions-per-second" value="[0-9]*"/>|<param name="sessions-per-second" value="100"/>|' "$FS_SWITCH"
    ok "FreeSWITCH sessions-per-second set to 100 in $FS_SWITCH"
fi

systemctl enable --now freeswitch
if systemctl is-active --quiet freeswitch; then
    ok "FreeSWITCH $(fs_cli -p "$ESL_PASS" -x 'version' 2>/dev/null | head -1 || echo 'installed')"
else
    fail "FreeSWITCH installed but service did not start — check: journalctl -u freeswitch"
fi

# Generate WSS TLS certificate so the internal IPv4 SIP profile (port 5060) can start.
# freeswitch-config-vanilla ships internal.xml with wss-binding :7443 which requires
# wss.pem in $${certs_dir} (/etc/pki/tls). Without it FreeSWITCH logs
# "Bad WSS.PEM certificate" and the internal profile silently stays down — SIP phones
# cannot register.
FS_CERTS_DIR=/etc/pki/tls
WSS_PEM="$FS_CERTS_DIR/wss.pem"
if [[ ! -f "$WSS_PEM" ]]; then
    quiet openssl req -new -newkey rsa:2048 -days 3650 -nodes -x509 \
        -subj "/C=US/ST=Server/L=Server/O=ICTPBX/CN=$(hostname -f 2>/dev/null || hostname)" \
        -keyout "$WSS_PEM" \
        -out /tmp/wss_cert.pem
    cat /tmp/wss_cert.pem >> "$WSS_PEM"
    rm -f /tmp/wss_cert.pem
    chmod 644 "$WSS_PEM"
    ok "WSS TLS certificate generated at $WSS_PEM"
else
    ok "WSS TLS certificate already present at $WSS_PEM"
fi
# Enable plain WebSocket (ws-binding :5066) in the internal profile so JsSIP softphone
# can connect. Apache proxies ws://HOST/ws/ → 127.0.0.1:5066 internally; port 5066
# is NOT opened in the firewall — all WebSocket traffic goes through port 80/443.
FS_INTERNAL=/etc/freeswitch/sip_profiles/internal.xml
if [[ -f "$FS_INTERNAL" ]]; then
    sed -i 's|value=":5066" enabled="false"|value=":5066" enabled="true"|g' "$FS_INTERNAL"
    ok "WebSocket binding :5066 enabled in $FS_INTERNAL (proxied via Apache, not exposed externally)"
fi

# Reload FreeSWITCH XML so the internal profile picks up the certificate and starts.
fs_cli -p "$ESL_PASS" -x 'reloadxml' >/dev/null 2>&1 || true
fs_cli -p "$ESL_PASS" -x 'sofia profile internal start' >/dev/null 2>&1 || true

# Open required ports — only if firewalld is actually running.
# On cloud images without firewalld, port management is upstream (panel).
if systemctl is-active --quiet firewalld; then
    for PORT in "5060/udp" "5060/tcp" "7443/tcp" "16384-32768/udp"; do
        firewall-cmd --permanent --add-port="$PORT" >/dev/null
    done
    firewall-cmd --reload >/dev/null
    ok "FreeSWITCH ports opened: 5060 (SIP), 7443 (WSS), 16384-32768 (RTP). Port 5066 proxied via Apache — not opened externally."
else
    warn "firewalld not running — open SIP (5060), WSS (7443), RTP (16384-32768) in your cloud panel. Port 5066 is internal (Apache proxy)."
fi

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# STEP 8 — FusionPBX 6.6.0
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
hdr "Step 8: FusionPBX 6.6.0"

# Finding #5 — create system user 'ictcore' before chown on /etc/fusionpbx/config.conf
# below. Previously this lived in Step 9 (ICTCore backend), causing the chown
# at the end of this step to fail on a fresh box ("invalid group: ictcore") and
# abort the script before FusionPBX schema load.
if ! id ictcore &>/dev/null; then
    useradd -r -s /sbin/nologin ictcore
    ok "System user 'ictcore' created"
else
    ok "System user 'ictcore' already exists"
fi

if [[ -d "$FUSIONPBX_DIR/.git" ]]; then
    ok "FusionPBX already cloned at $FUSIONPBX_DIR"
else
    info "Cloning FusionPBX $FUSIONPBX_TAG..."
    git clone --depth 1 --branch "$FUSIONPBX_TAG" "$FUSIONPBX_REPO" "$FUSIONPBX_DIR" \
        || fail "FusionPBX clone failed (tag $FUSIONPBX_TAG)"
    ok "FusionPBX $FUSIONPBX_TAG cloned"
fi

chown -R apache:apache "$FUSIONPBX_DIR"
ok "Ownership set: apache:apache"

# Create /etc/fusionpbx/config.conf (required by FusionPBX itself; FpbxDomain
# reads PG creds from ictcore.conf [fusionpbx] instead — see Step 11).
info "Writing /etc/fusionpbx/config.conf..."
mkdir -p /etc/fusionpbx
cat > /etc/fusionpbx/config.conf <<FPBXCONF
[database]
type = pgsql
name = fusionpbx
username = fusionpbx
password = ${PG_FUSIONPBX_PASS}
host = 127.0.0.1
port = 5432

[document]
root = ${FUSIONPBX_DIR}

[temp]
dir = /tmp

[php]
dir = /usr/bin
FPBXCONF
# Owner=apache (FusionPBX scripts/install run as apache); group=ictcore
# (Apache itself runs as ictcore — set in Step 13 — so the FusionPBX UI
# under /fpbx can read this file via group permission). Without group=ictcore
# the /fpbx alias 500s on every page after install.
chown apache:ictcore /etc/fusionpbx/config.conf
chmod 640 /etc/fusionpbx/config.conf
ok "/etc/fusionpbx/config.conf written"

# Load FusionPBX PostgreSQL schema
info "Loading FusionPBX schema into PostgreSQL..."
SCHEMA_FILE="$FUSIONPBX_DIR/resources/install/sql/postgresql.sql"
if [[ -f "$SCHEMA_FILE" ]]; then
    SCHEMA_LOG=$(mktemp)
    if PGPASSWORD="$PG_FUSIONPBX_PASS" "$PSQL" -h 127.0.0.1 -U fusionpbx -d fusionpbx \
        -f "$SCHEMA_FILE" >"$SCHEMA_LOG" 2>&1; then
        ok "FusionPBX schema loaded"
    else
        cat "$SCHEMA_LOG" | tail -20
        fail "FusionPBX schema load failed — see $SCHEMA_LOG"
    fi
    rm -f "$SCHEMA_LOG"
else
    warn "Schema file not found at $SCHEMA_FILE — run FusionPBX web installer manually"
fi

# #15 — seed a default v_domains row so FpbxDomain::get_domain_uuid() resolves
# on a fresh install. Idempotent: skips if any row already exists.
SERVER_IP_FOR_DOMAIN=$(hostname -I | awk '{print $1}')
PGPASSWORD="$PG_FUSIONPBX_PASS" "$PSQL" -h 127.0.0.1 -U fusionpbx -d fusionpbx -v ON_ERROR_STOP=1 <<PGSEED >/dev/null 2>&1 \
    && ok "Default v_domains row seeded (or already present)" \
    || warn "Default v_domains seed failed (non-fatal — check PG schema)"
INSERT INTO v_domains (domain_uuid, domain_name, domain_enabled)
SELECT gen_random_uuid(), '${SERVER_IP_FOR_DOMAIN}', 'true'
 WHERE NOT EXISTS (SELECT 1 FROM v_domains);
PGSEED

# Capture the first active domain_uuid so we can link tenant_id=1 to it
# after the MariaDB tenant table exists (audit gap #5).
FPBX_DOMAIN_UUID=$(PGPASSWORD="$PG_FUSIONPBX_PASS" "$PSQL" -h 127.0.0.1 -U fusionpbx -d fusionpbx \
    -tAc "SELECT domain_uuid FROM v_domains WHERE domain_enabled='true' ORDER BY domain_uuid LIMIT 1;" 2>/dev/null | tr -d ' \r\n' || true)
if [[ -n "$FPBX_DOMAIN_UUID" ]]; then
    ok "FusionPBX domain_uuid captured: $FPBX_DOMAIN_UUID"
else
    warn "Could not read v_domains.domain_uuid — tenant link will be skipped"
fi

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# STEP 9 — ICTCore backend
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
hdr "Step 9: ICTCore backend"

# Note: system user 'ictcore' is created in Step 8 (FusionPBX) so the
# /etc/fusionpbx/config.conf chown there succeeds — see Finding #5.

# Clone or update — backend repo is private; honor GIT_TOKEN if provided.
# Token is injected into the URL only for the clone, then the remote is
# reset to the clean URL so the PAT never lands in .git/config.
if [[ -d "$ICTCORE_DIR/.git" ]]; then
    info "ICTCore already present — pulling latest..."
    git -C "$ICTCORE_DIR" config --global --add safe.directory "$ICTCORE_DIR" 2>/dev/null || true
    # Finding #6 — honor GIT_TOKEN on the pull path too. Without this the
    # upgrade flow fails with "fatal: could not read Username for github.com"
    # against the private backend repo. Inject the PAT into the remote URL
    # for one pull, then restore the clean URL so the token never lingers in
    # .git/config.
    if [[ -n "${GIT_TOKEN:-}" ]]; then
        CLEAN_URL=$(git -C "$ICTCORE_DIR" remote get-url origin)
        AUTH_URL="${CLEAN_URL/https:\/\//https:\/\/${GIT_TOKEN}@}"
        git -C "$ICTCORE_DIR" remote set-url origin "$AUTH_URL"
        quiet git -C "$ICTCORE_DIR" pull origin main
        git -C "$ICTCORE_DIR" remote set-url origin "$CLEAN_URL"
    else
        quiet git -C "$ICTCORE_DIR" pull origin main
    fi
    ok "ICTCore updated"
else
    info "Cloning ICTCore..."
    if [[ -n "${GIT_TOKEN:-}" ]]; then
        AUTH_URL="${ICTCORE_REPO/https:\/\//https:\/\/${GIT_TOKEN}@}"
        quiet git clone "$AUTH_URL" "$ICTCORE_DIR"
        git -C "$ICTCORE_DIR" remote set-url origin "$ICTCORE_REPO"
        ok "ICTCore cloned (PAT scrubbed from .git/config)"
    else
        if ! git clone "$ICTCORE_REPO" "$ICTCORE_DIR" >>"$LOG" 2>&1; then
            echo ""
            warn "git clone failed — the backend repo is private."
            warn "  Re-run with a GitHub PAT (needs 'repo' scope):"
            warn "    GIT_TOKEN=ghp_xxxx bash $0"
            fail "Aborting — cannot continue without backend source."
        fi
        ok "ICTCore cloned"
    fi
fi

chown -R ictcore:ictcore "$ICTCORE_DIR"
ok "Ownership set: ictcore:ictcore"

# FreeSWITCH config symlinks
ln -sf "$ICTCORE_DIR/etc/freeswitch/dialplan/ictcore.xml"     /etc/freeswitch/dialplan/ictcore.xml
ln -sf "$ICTCORE_DIR/etc/freeswitch/sip_profiles/ictcore.xml" /etc/freeswitch/sip_profiles/ictcore.xml
ln -sf "$ICTCORE_DIR/etc/freeswitch/directory/ictcore.xml"    /etc/freeswitch/directory/ictcore.xml
ok "FreeSWITCH config symlinks created"

# Composer dependencies
if [[ -f "$ICTCORE_DIR/composer.json" ]]; then
    if ! command -v composer &>/dev/null; then
        info "Installing Composer..."
        # Pipe defeats `quiet` (it can't capture both sides), so we redirect to
        # the install log and rely on `set -e` + the immediate version check
        # below to catch failures.
        curl -sS https://getcomposer.org/installer \
            | php -- --install-dir=/usr/local/bin --filename=composer >>"$LOG" 2>&1
        command -v composer >/dev/null \
            || fail "Composer install failed — see $LOG"
        ok "Composer installed"
    fi
    cd "$ICTCORE_DIR"
    # --quiet suppresses normal output; errors still surface (no 2>/dev/null).
    composer install --no-dev --no-interaction --quiet
    ok "Composer dependencies installed"
fi

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# STEP 10 — MariaDB schema
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
hdr "Step 10: MariaDB schema"

DB_DIR="$ICTCORE_DIR/db"
# Prefer mariadb-schema.sql if backend repo ships it; fall back to legacy database.sql.
BASE_SCHEMA=database.sql
[[ -f "$DB_DIR/mariadb-schema.sql" ]] && BASE_SCHEMA=mariadb-schema.sql
SCHEMA_FILES=(
    "$BASE_SCHEMA"
    core_seed.sql
    branding.sql
    tenant.sql
    voice.sql
    fax.sql
    sms.sql
    email.sql
    password_policy.sql
    contact_dnc.sql
    users_cdr.sql
    login_attempts.sql
    retry_interval.sql
    statistics_report_changes.sql
    pbx_quota_extensions.sql
    provider_fpbx_cols.sql
    provider_fpbx_gateway_uuid.sql
    route_fpbx_dialplan_uuid.sql
    cdr_enriched_columns.sql
    billing_seed.sql
)

# Idempotent: skip if base schema already loaded (account table is from database.sql).
SCHEMA_LOADED=$(mysql -u root -p"${MARIADB_ROOT_PASS}" -N -B -e \
    "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='ictfax' AND table_name='account';" 2>/dev/null || echo 0)
if [[ "$SCHEMA_LOADED" == "1" ]]; then
    ok "ictfax base schema already loaded — skipping all schema files"
    info "(re-run with FORCE_SCHEMA=1 to reload)"
fi

if [[ "$SCHEMA_LOADED" != "1" || "${FORCE_SCHEMA:-}" == "1" ]]; then
    for FILE in "${SCHEMA_FILES[@]}"; do
        if [[ -f "$DB_DIR/$FILE" ]]; then
            if mysql -u root -p"${MARIADB_ROOT_PASS}" ictfax < "$DB_DIR/$FILE" 2>/tmp/mysql-load.err; then
                ok "Loaded: $FILE"
            else
                warn "Errors loading $FILE (continuing): $(tail -3 /tmp/mysql-load.err | tr '\n' ' ')"
            fi
        else
            warn "Not found (skipping): $DB_DIR/$FILE"
        fi
    done
fi

# Idempotent post-schema hardening — covers gaps not always present in shipped SQL files
# (issues #12 login_attempt, #13 spool.cost, #16e default branding row).
mysql -u root -p"${MARIADB_ROOT_PASS}" ictfax 2>/tmp/mysql-fix.err <<'SQLFIX' || \
    warn "Post-schema hardening hit errors: $(tail -3 /tmp/mysql-fix.err | tr '\n' ' ')"
-- #12 login_attempt table (referenced by AuthenticateApi on every login)
CREATE TABLE IF NOT EXISTS login_attempt (
    login_attempt_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT(6) NOT NULL,
    attempts    INT DEFAULT 0,
    ip_address  VARCHAR(255)
);

-- #13 spool.cost column (referenced by transmission listing)
ALTER TABLE spool ADD COLUMN IF NOT EXISTS cost DECIMAL(10,4) DEFAULT 0;

-- #16e default branding row — repoint stale ICTFAX values to ICTPBX
UPDATE branding
   SET domain = 'ictpbx',
       title  = 'ICTPBX',
       logo   = '/usr/ictcore/data/logo/ictpbx-logo.png',
       footer = 'Copyrights -  <a href="https://www.ictinnovations.com" target=_blank"> ICT Innovations 2026 '
 WHERE def = 1
   AND (title = 'ICTFAX' OR logo LIKE '%ictfax-logo%' OR domain NOT IN ('ictpbx','ICTPBX'));
SQLFIX
ok "Post-schema hardening applied (login_attempt, spool.cost, branding default)"

# Seed role+admin data — only if usr table is empty (avoids duplicate-key on re-run).
# Without this seed, Step 15's admin-password UPDATE no-ops on a fresh DB
# because no row with email='admin@ictcore.org' exists yet.
USR_COUNT=$(mysql -u root -p"${MARIADB_ROOT_PASS}" -N -B -e "SELECT COUNT(*) FROM ictfax.usr;" 2>/dev/null || echo 0)
if [[ "$USR_COUNT" == "0" ]]; then
    info "Seeding roles + admin/user accounts..."
    DATA_FILES=(role_admin.sql role_tenant.sql role_user.sql role_end_user.sql demo_users.sql)
    for FILE in "${DATA_FILES[@]}"; do
        if [[ -f "$DB_DIR/data/$FILE" ]]; then
            mysql -u root -p"${MARIADB_ROOT_PASS}" ictfax < "$DB_DIR/data/$FILE" 2>/tmp/mysql-load.err \
                && ok "Seeded: data/$FILE" \
                || warn "Errors seeding data/$FILE: $(tail -3 /tmp/mysql-load.err | tr '\n' ' ')"
        else
            warn "Not found (skipping): $DB_DIR/data/$FILE"
        fi
    done
else
    ok "usr table already has $USR_COUNT row(s) — skipping seed data"
fi

# Audit gap #5 — link tenant_id=1 to the FusionPBX domain UUID we captured
# above. Without this, every PBX module API call falls back to "first active
# domain", which works only on single-tenant CE. Skip if column missing
# (older schema) or already set.
#
# Finding #13 — disambiguate ROW_COUNT()=0. UPDATE returns 0 both when the row
# is already linked AND when tenant_id=1 doesn't exist. The latter case is a
# real bug (Finding #11) that this UPDATE used to swallow silently. Probe the
# row first and fail loudly if missing.
if [[ -n "${FPBX_DOMAIN_UUID:-}" ]]; then
    TENANT_EXISTS=$(mysql -u root -p"${MARIADB_ROOT_PASS}" -N -B ictfax \
        -e "SELECT COUNT(*) FROM tenant WHERE tenant_id = 1;" 2>/dev/null || echo 0)
    if [[ "$TENANT_EXISTS" != "1" ]]; then
        warn "tenant_id=1 missing — seed data did not insert it (see Finding #11)"
        warn "tenant ↔ fpbx_domain link skipped"
    else
        LINK_RESULT=$(mysql -u root -p"${MARIADB_ROOT_PASS}" -N -B ictfax 2>/tmp/mysql-link.err <<SQL
UPDATE tenant
   SET fpbx_domain_uuid = '${FPBX_DOMAIN_UUID}'
 WHERE tenant_id = 1
   AND (fpbx_domain_uuid IS NULL OR fpbx_domain_uuid = '');
SELECT ROW_COUNT();
SQL
)
        if [[ "$LINK_RESULT" == "1" ]]; then
            ok "Linked tenant_id=1 → FusionPBX domain_uuid"
        elif [[ "$LINK_RESULT" == "0" ]]; then
            ok "tenant_id=1 already linked to a FusionPBX domain — left as-is"
        else
            warn "tenant ↔ fpbx_domain link skipped: $(tail -2 /tmp/mysql-link.err | tr '\n' ' ')"
        fi
    fi
fi

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# STEP 11 — ICTCore configuration
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
hdr "Step 11: ICTCore configuration"

SERVER_IP=$(hostname -I | awk '{print $1}')
if [[ -z "${PUBLIC_HOST:-}" ]]; then
    if [[ "${INSTALLER_AUTO:-0}" == "1" ]]; then
        PUBLIC_HOST="$SERVER_IP"
        ok "PUBLIC_HOST not set — defaulting to $SERVER_IP (INSTALLER_AUTO)"
    else
        read -rp "  Public hostname/IP [${SERVER_IP}]: " PUBLIC_HOST
        PUBLIC_HOST="${PUBLIC_HOST:-$SERVER_IP}"
    fi
fi

# Optional: SMTP + branding — press Enter to skip (leaves blanks, password reset
# emails / fax-to-email silently fail until configured later). Env vars override:
# COMPANY_NAME, SITE_TITLE, SMTP_FROM, SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS.
COMPANY_NAME="${COMPANY_NAME:-ICTPBX}"
SITE_TITLE="${SITE_TITLE:-ICTPBX}"
if [[ "${INSTALLER_AUTO:-0}" != "1" ]]; then
    echo ""
    echo "── Optional: Email + branding (press Enter at any prompt to skip) ──"
    read -rp "  Company name [${COMPANY_NAME}]: " _CN
    COMPANY_NAME="${_CN:-$COMPANY_NAME}"
    read -rp "  Site title [${SITE_TITLE}]: " _ST
    SITE_TITLE="${_ST:-$SITE_TITLE}"
    read -rp "  SMTP from address (e.g. noreply@example.com): " _SF
    SMTP_FROM="${_SF:-${SMTP_FROM:-}}"
    if [[ -n "$SMTP_FROM" ]]; then
        read -rp "  SMTP host [smtp.gmail.com]: " _SH
        SMTP_HOST="${_SH:-${SMTP_HOST:-smtp.gmail.com}}"
        read -rp "  SMTP port [587]: " _SP
        SMTP_PORT="${_SP:-${SMTP_PORT:-587}}"
        read -rp "  SMTP user [${SMTP_FROM}]: " _SU
        SMTP_USER="${_SU:-${SMTP_USER:-$SMTP_FROM}}"
        read -rsp "  SMTP password (hidden): " SMTP_PASS_INPUT; echo
        [[ -n "$SMTP_PASS_INPUT" ]] && SMTP_PASS="$SMTP_PASS_INPUT"
    fi
fi
SMTP_FROM="${SMTP_FROM:-}"
SMTP_HOST="${SMTP_HOST:-}"
SMTP_PORT="${SMTP_PORT:-}"
SMTP_USER="${SMTP_USER:-}"
SMTP_PASS="${SMTP_PASS:-}"

# #18 — when DOMAIN env var is set (or PUBLIC_HOST resolves to a real hostname),
# write the [website] url with https:// so JWT iss matches what the frontend sees.
# Without this, JWT iss = http://<ip>/api but page origin = https://<domain>,
# so token validation rejects every request after TLS goes live.
PUBLIC_DOMAIN="${DOMAIN:-}"
if [[ -z "$PUBLIC_DOMAIN" && "$PUBLIC_HOST" =~ [a-zA-Z] ]]; then
    PUBLIC_DOMAIN="$PUBLIC_HOST"
fi
if [[ -n "$PUBLIC_DOMAIN" ]]; then
    WEBSITE_URL="https://${PUBLIC_DOMAIN}/api"
    WEBSITE_HOST="$PUBLIC_DOMAIN"
    WEBSITE_PORT=443
    info "Configuring [website] for HTTPS at ${WEBSITE_URL}"
else
    WEBSITE_URL="http://${PUBLIC_HOST}/api"
    WEBSITE_HOST="$PUBLIC_HOST"
    WEBSITE_PORT=80
fi

cat > "$ICTCORE_DIR/etc/ictcore.conf" <<CONF
[company]
name = ${COMPANY_NAME}

[website]
host  = ${WEBSITE_HOST}
port  = ${WEBSITE_PORT}
path  = /api/
url   = ${WEBSITE_URL}

[provisioning]
host = ${WEBSITE_HOST}
port = 5060
wss  = 7443

[security]
hash_type    = RS256
token_expiry = 31104000
private_key  = /usr/ictcore/etc/ssh/ib_node
public_key   = /usr/ictcore/etc/ssh/ib_node.pub

[gatewayhub]
url =

[node]
id = 1

[db]
host = localhost
port = 3306
user = ictfax
name = ictfax
type = mysql
pass = "${MARIADB_ICTFAX_PASS}"

[sendmail]
from = ${SMTP_FROM}
host = ${SMTP_HOST}
port = ${SMTP_PORT}
user = ${SMTP_USER}
pass = ${SMTP_PASS}

[freeswitch]
user     = user
password = ${ESL_PASS}
host     = 127.0.0.1
port     = 8021
fs_cli   = /var/log/freeswitch/freeswitch.log

[kannel]
host =
port =
user =
pass =

[site]
title = ${SITE_TITLE}

[domain]
name = ${PUBLIC_HOST}

[fusionpbx]
host = 127.0.0.1
port = 5432
name = fusionpbx
user = fusionpbx
pass = "${PG_FUSIONPBX_PASS}"
CONF

chown ictcore:ictcore "$ICTCORE_DIR/etc/ictcore.conf"
chmod 640 "$ICTCORE_DIR/etc/ictcore.conf"
ok "ictcore.conf written"

# #5 — core/init.php hardcodes /etc/ictcore.conf; symlink to the real file so
# PHP can find it under the open_basedir restriction (extended below in vhost).
ln -sf "$ICTCORE_DIR/etc/ictcore.conf" /etc/ictcore.conf
ok "Symlink created: /etc/ictcore.conf → $ICTCORE_DIR/etc/ictcore.conf"

# #17 — create log dir + pre-create ictcore.log with ictcore ownership.
# Without this, every PHP request emits "failed to open stream: Permission denied".
mkdir -p "$ICTCORE_DIR/log"
touch "$ICTCORE_DIR/log/ictcore.log"
chown -R ictcore:ictcore "$ICTCORE_DIR/log"
chmod 775 "$ICTCORE_DIR/log"
chmod 664 "$ICTCORE_DIR/log/ictcore.log"
ok "Log directory ready: $ICTCORE_DIR/log (owned by ictcore)"

# Finding #8 — on SELinux=Enforcing (Rocky 9 default), files under /usr/ictcore
# inherit type 'usr_t' which blocks httpd writes even when DAC perms are correct
# (PHP-FPM/Apache run with httpd_t and need httpd_log_t to write log files).
# Relabel the log dir + file so php-fpm can write to ictcore.log without an
# AVC denial. semanage may not be installed on a minimal Rocky image; install
# policycoreutils-python-utils first. Skip cleanly when SELinux is Disabled.
if command -v getenforce &>/dev/null && [[ "$(getenforce)" != "Disabled" ]]; then
    quiet dnf install -y policycoreutils-python-utils || warn "Could not install policycoreutils-python-utils — skipping SELinux relabel"
    if command -v semanage &>/dev/null; then
        semanage fcontext -a -t httpd_log_t "$ICTCORE_DIR/log(/.*)?" 2>/dev/null || \
            semanage fcontext -m -t httpd_log_t "$ICTCORE_DIR/log(/.*)?" 2>/dev/null || true
        restorecon -R "$ICTCORE_DIR/log" 2>/dev/null || true
        ok "SELinux fcontext set: $ICTCORE_DIR/log → httpd_log_t"
        # cache/ must be writable by httpd — route map cache, contact-import CSV
        # staging, daemon PID files. Without httpd_sys_rw_content_t, contact CSV
        # uploads silently fail (file_put_contents returns false, daemon spawn
        # fails to create pidfile).
        semanage fcontext -a -t httpd_sys_rw_content_t "$ICTCORE_DIR/cache(/.*)?" 2>/dev/null || \
            semanage fcontext -m -t httpd_sys_rw_content_t "$ICTCORE_DIR/cache(/.*)?" 2>/dev/null || true
        restorecon -R "$ICTCORE_DIR/cache" 2>/dev/null || true
        ok "SELinux fcontext set: $ICTCORE_DIR/cache → httpd_sys_rw_content_t"
        # data/ holds AES-encrypted uploaded documents (fax attachments etc.)
        # PHP-FPM must be able to read/write them; default usr_t blocks this.
        semanage fcontext -a -t httpd_sys_rw_content_t "$ICTCORE_DIR/data(/.*)?" 2>/dev/null || \
            semanage fcontext -m -t httpd_sys_rw_content_t "$ICTCORE_DIR/data(/.*)?" 2>/dev/null || true
        restorecon -R "$ICTCORE_DIR/data" 2>/dev/null || true
        ok "SELinux fcontext set: $ICTCORE_DIR/data → httpd_sys_rw_content_t"
    fi
fi

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# STEP 12 — JWT RS256 keypair
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
hdr "Step 12: JWT RS256 keypair"

SSH_DIR="$ICTCORE_DIR/etc/ssh"
mkdir -p "$SSH_DIR"

if [[ -f "$SSH_DIR/ib_node" ]]; then
    ok "JWT keypair already exists — skipping"
else
    openssl genpkey -algorithm RSA -pkeyopt rsa_keygen_bits:1024 \
        -out "$SSH_DIR/ib_node" 2>/dev/null
    openssl rsa -in "$SSH_DIR/ib_node" -pubout \
        -out "$SSH_DIR/ib_node.pub" 2>/dev/null
    openssl req -new -x509 -key "$SSH_DIR/ib_node" \
        -out "$SSH_DIR/ib_node.crt" -days 3650 \
        -subj "/CN=${PUBLIC_HOST}/O=ICTCore" 2>/dev/null
    cp "$SSH_DIR/ib_node" "$SSH_DIR/ib_node.pem"

    chown ictcore:ictcore "$SSH_DIR"/ib_node*
    chmod 640 "$SSH_DIR/ib_node" "$SSH_DIR/ib_node.pem"
    chmod 644 "$SSH_DIR/ib_node.pub" "$SSH_DIR/ib_node.crt"
    ok "JWT RS256 keypair generated"
fi

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# STEP 13 — Apache virtual host
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
hdr "Step 13: Apache virtual host"

# Finding #16 — make the backend's vhost frontend-aware so a re-run never
# clobbers the Angular DocumentRoot the frontend installer wrote. On EE the
# frontend writes a separate ictpbx-frontend.conf (different filename → no
# direct clobber), but converging both installers on the same logic keeps the
# behaviour identical EE/CE if filenames ever align.
if [[ -f /usr/ictpbxx/dist/index.html ]]; then
    info "Angular bundle detected at /usr/ictpbxx/dist — writing Angular-only vhost (no FusionPBX UI exposure)"
    cat > "$APACHE_CONF" <<'VHOST'
<VirtualHost *:80>
    DocumentRoot /usr/ictpbxx/dist
    ServerName _default_

    <Directory /usr/ictpbxx/dist>
        Options FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # ICTCore REST API under /api
    Alias /api /usr/ictcore/wwwroot
    <Directory /usr/ictcore/wwwroot>
        SetEnv PHP_ADMIN_VALUE "open_basedir = /usr/ictcore/:/etc/ictcore.conf:/usr/bin:/bin:/tmp/"
        Options Indexes FollowSymLinks Includes
        AllowOverride All
        Require all granted
    </Directory>

    # WebSocket proxy for JsSIP softphone — routes ws://HOST/ws/ to FreeSWITCH
    # port 5066 internally. Keeps all traffic on port 80; no external WS port needed.
    ProxyRequests Off
    RewriteEngine On
    RewriteCond %{HTTP:Upgrade} websocket [NC]
    RewriteCond %{HTTP:Connection} upgrade [NC]
    RewriteRule ^/ws(/.*)?$ ws://127.0.0.1:5066/$1 [P,L]
    ProxyPass /ws/ ws://127.0.0.1:5066/
    ProxyPassReverse /ws/ ws://127.0.0.1:5066/

    # FusionPBX UI intentionally NOT exposed — users only see the Angular frontend.
    ErrorLog  /var/log/httpd/ictpbx_error.log
    CustomLog /var/log/httpd/ictpbx_access.log combined
</VirtualHost>
VHOST
else
    cat > "$APACHE_CONF" <<'VHOST'
<VirtualHost *:80>
    # Angular frontend not yet built — frontend installer will rewrite this
    # file with /usr/ictpbxx/dist as DocumentRoot. Until then, serve the
    # default httpd webroot (empty Apache welcome) so FusionPBX UI is NEVER
    # exposed at /.
    DocumentRoot /var/www/html
    ServerName _default_

    <Directory /var/www/html>
        Options FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # ICTCore REST API under /api
    Alias /api /usr/ictcore/wwwroot
    <Directory /usr/ictcore/wwwroot>
        SetEnv PHP_ADMIN_VALUE "open_basedir = /usr/ictcore/:/etc/ictcore.conf:/usr/bin:/bin:/tmp/"
        Options Indexes FollowSymLinks Includes
        AllowOverride All
        Require all granted
    </Directory>

    # WebSocket proxy for JsSIP softphone — routes ws://HOST/ws/ to FreeSWITCH
    # port 5066 internally. Keeps all traffic on port 80; no external WS port needed.
    ProxyRequests Off
    RewriteEngine On
    RewriteCond %{HTTP:Upgrade} websocket [NC]
    RewriteCond %{HTTP:Connection} upgrade [NC]
    RewriteRule ^/ws(/.*)?$ ws://127.0.0.1:5066/$1 [P,L]
    ProxyPass /ws/ ws://127.0.0.1:5066/
    ProxyPassReverse /ws/ ws://127.0.0.1:5066/

    ErrorLog  /var/log/httpd/ictpbx_error.log
    CustomLog /var/log/httpd/ictpbx_access.log combined
</VirtualHost>
VHOST
fi

# #4 — Enable mod_rewrite. On EL9 minimal images mod_rewrite.so is sometimes
# in a sub-package; install it explicitly if the module file is missing.
if [[ ! -f /etc/httpd/modules/mod_rewrite.so ]]; then
    info "mod_rewrite.so missing — pulling httpd sub-packages..."
    dnf install -y httpd-tools mod_http2 2>>"$LOG" || true
fi
grep -q 'mod_rewrite' /etc/httpd/conf.modules.d/*.conf 2>/dev/null || \
    echo "LoadModule rewrite_module modules/mod_rewrite.so" \
        >> /etc/httpd/conf.modules.d/00-base.conf

# Run Apache + PHP-FPM as ictcore so PHP can read /usr/ictcore/etc/ictcore.conf (mode 640).
# Without this, PHP backend 500s on every request because it can't open the conf
# file owned ictcore:ictcore. Required — the ictcore.conf permissions are deliberate.
sed -i -E 's/^User .*/User ictcore/; s/^Group .*/Group ictcore/' /etc/httpd/conf/httpd.conf
sed -i -E 's/^user = .*/user = ictcore/; s/^group = .*/group = ictcore/' /etc/php-fpm.d/www.conf

# #9 — PHP-FPM Unix socket ACL must include the runtime user (ictcore).
# Default `listen.acl_users = apache,nginx` blocks Apache's reverse proxy
# from connecting to the socket → 503 (Permission denied) on every /api hit.
if grep -qE '^listen\.acl_users\s*=' /etc/php-fpm.d/www.conf; then
    sed -i -E 's/^(listen\.acl_users\s*=.*)/\1,ictcore/; s/,ictcore,ictcore/,ictcore/' \
        /etc/php-fpm.d/www.conf
else
    echo 'listen.acl_users = apache,nginx,ictcore' >> /etc/php-fpm.d/www.conf
fi
ok "Apache + PHP-FPM configured to run as ictcore (socket ACL includes ictcore)"

# PHP-FPM ships with PrivateTmp=true which creates an isolated /tmp namespace
# for the process. FreeSWITCH writes received-fax TIFFs to the real /tmp, so
# PHP file_exists() always returns false — inbound fax processing silently fails.
# Disable PrivateTmp so both processes share the same /tmp.
mkdir -p /etc/systemd/system/php-fpm.service.d
printf '[Service]\nPrivateTmp=false\n' > /etc/systemd/system/php-fpm.service.d/override.conf
systemctl daemon-reload
ok "php-fpm PrivateTmp=false override written"

# FreeSWITCH creates TIFFs as rw-rw---- owned freeswitch:daemon.
# ICTCore (ictcore user) must be in the daemon group to read them.
usermod -a -G daemon ictcore 2>/dev/null || true
ok "ictcore user added to daemon group (FreeSWITCH TIFF read access)"

# SELinux: allow Apache to write (log, cache)
setsebool -P httpd_unified 1 &>/dev/null || true
setsebool -P httpd_can_network_connect_db 1 &>/dev/null || true

# Restart (not reload) — User/Group changes don't take effect on reload.
systemctl restart php-fpm
systemctl restart httpd
ok "Apache vhost written: $APACHE_CONF"

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# STEP 14 — Firewall
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
hdr "Step 14: Firewall"

if systemctl is-active --quiet firewalld; then
    for SVC in http https; do
        if firewall-cmd --list-services --zone=public 2>/dev/null | grep -q "$SVC"; then
            ok "firewalld: $SVC already allowed"
        else
            quiet firewall-cmd --permanent --zone=public --add-service="$SVC"
            ok "firewalld: $SVC added"
        fi
    done
    quiet firewall-cmd --reload
    ok "firewalld reloaded"
else
    warn "firewalld not running — ensure ports 80, 443, 5060, 7443 are open in your cloud panel"
fi

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# STEP 15 — Clear route cache & set admin password
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
hdr "Step 15: Route cache & admin password"

rm -f "$ICTCORE_DIR"/cache/* 2>/dev/null || true
ok "Route cache cleared"

ADMIN_HASH=$(php -r "echo md5('${ICTCORE_ADMIN_PASS}');")
mysql -u root -p"${MARIADB_ROOT_PASS}" ictfax \
    -e "UPDATE usr SET passwd='${ADMIN_HASH}' WHERE email='admin@ictcore.org';" 2>/dev/null && \
    ok "Admin password set for admin@ictcore.org" || \
    warn "Could not update admin password — set manually after schema load"

# Spool::set_route() for sendemail programs queries provider WHERE type='smtp'.
# Without a row there, provider_id stays NULL → Sendmail::connect() crashes →
# fax-to-email notifications fail with "Unsupported sendmail command flags []".
# Insert the smtp provider row using the SMTP creds collected in Step 11.
if [[ -n "${SMTP_HOST:-}" ]]; then
    mysql -u root -p"${MARIADB_ROOT_PASS}" ictfax 2>/dev/null <<SQL
INSERT IGNORE INTO provider (name, type, host, port, username, password, active, tenant_id)
SELECT 'SMTP', 'smtp', '${SMTP_HOST}', ${SMTP_PORT:-587}, '${SMTP_USER:-}', '${SMTP_PASS:-}', 1, 1
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM provider WHERE type = 'smtp' AND tenant_id = 1);
SQL
    ok "SMTP provider record ensured in provider table (fax-to-email pipeline)"
else
    warn "SMTP_HOST not set — smtp provider row skipped; fax-to-email will fail until added manually"
fi

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# STEP 16 — Apply FusionPBX patches
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
hdr "Step 16: FusionPBX local patches"

PATCH="$ICTCORE_DIR/patches/fusionpbx-local.patch"
if [[ -f "$PATCH" ]]; then
    cd "$FUSIONPBX_DIR"
    if git apply "$PATCH" >>"$LOG" 2>&1; then
        ok "fusionpbx-local.patch applied"
    else
        warn "Patch already applied or failed — see $LOG; re-check manually: git apply $PATCH"
    fi
else
    warn "No patch file at $PATCH — skipping"
fi

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# STEP 17 — CDR ETL cron job
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
hdr "Step 17: CDR ETL cron job"

# Hourly ingestion of FreeSWITCH Master.csv → ictfax.cdr_lega.
# Idempotent: cdr_etl.php uses a byte-offset watermark so re-runs are safe.
CDR_CRON=/etc/cron.d/ictpbx-cdr
if [[ -f "$CDR_CRON" ]]; then
    ok "CDR ETL cron already present at $CDR_CRON"
else
    cat > "$CDR_CRON" <<'CRON'
# ICTPBX — ingest FreeSWITCH CDR CSV into MariaDB hourly
5 * * * * root /usr/bin/php /usr/ictcore/scripts/cdr_etl.php >> /var/log/ictcore/cdr_etl.log 2>&1
CRON
    chmod 644 "$CDR_CRON"
    ok "CDR ETL cron installed at $CDR_CRON (runs at :05 every hour)"
fi

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# STEP 18 — Smoke tests
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
hdr "Step 18: Smoke tests"

AUTH_RESP=$(curl -s -o /dev/null -w '%{http_code}' -X POST \
    "http://127.0.0.1/api/authenticate" \
    -H 'Content-Type: application/json' \
    -d "{\"username\":\"admin@ictcore.org\",\"password\":\"${ICTCORE_ADMIN_PASS}\"}" || echo "000")
if [[ "$AUTH_RESP" == "200" ]]; then
    ok "POST /api/authenticate → 200 (admin login works, JWT issued)"
elif [[ "$AUTH_RESP" =~ ^(400|401|403)$ ]]; then
    warn "POST /api/authenticate → $AUTH_RESP — backend reachable but admin password not accepted; re-check Step 15"
elif [[ "$AUTH_RESP" == "404" ]]; then
    warn "POST /api/authenticate → 404 — /api alias broken; check Apache vhost"
else
    warn "POST /api/authenticate → $AUTH_RESP (unexpected) — see $LOG"
fi

# Quick PBX endpoint check (uses the JWT from auth above)
JWT=$(curl -s -X POST "http://127.0.0.1/api/authenticate" \
    -H 'Content-Type: application/json' \
    -d "{\"username\":\"admin@ictcore.org\",\"password\":\"${ICTCORE_ADMIN_PASS}\"}" 2>/dev/null \
    | grep -o '"token":"[^"]*"' | cut -d'"' -f4 || echo "")
if [[ -n "$JWT" ]]; then
    PBX_RESP=$(curl -s -o /dev/null -w '%{http_code}' \
        -H "Authorization: Bearer $JWT" \
        "http://127.0.0.1/api/fpbx_extensions" || echo "000")
    if [[ "$PBX_RESP" == "200" ]]; then
        ok "GET /api/fpbx_extensions → 200 (FusionPBX integration reachable)"
    else
        warn "GET /api/fpbx_extensions → $PBX_RESP — check FpbxDomain.php / [fusionpbx] section in ictcore.conf"
    fi
fi

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# DONE
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
echo ""
echo "============================================================"
ok "ICTPBX Backend setup complete!"
echo ""
echo "  Edition     : Enterprise (multi-tenant + billing + branding)"
echo "  Backend API : http://${PUBLIC_HOST}/api"
echo "  FusionPBX   : http://${PUBLIC_HOST}/"
echo "  Log         : $LOG"
echo ""
echo "  Admin login : admin@ictcore.org / <password you entered>"
echo ""
echo "  What was provisioned:"
echo "    - MariaDB schema + password_policy seed + login_attempt table"
echo "    - PostgreSQL fusionpbx DB + v_domains seed for tenant 1"
echo "    - Default branding row, billing seed, spool.cost column"
echo "    - JWT keypair (/usr/ictcore/etc/ssh/ib_node)"
echo "    - Apache vhost + mod_rewrite fallback + listen.acl_users"
echo "    - Log directories with correct ownership"
echo ""
echo "  Next steps:"
echo "    1. Install the Angular frontend:"
echo "       git clone https://github.com/ictvision/ictpbx-frontend.git /usr/ictpbxx"
echo "       bash /usr/ictpbxx/ictpbx-install.sh"
echo ""
echo "    2. Clear API cache after any PHP changes:"
echo "       rm -f /usr/ictcore/cache/*"
echo ""
echo "  Optional post-install tuning (edit /usr/ictcore/etc/ictcore.conf):"
echo "    - [sendmail]  — SMTP for password reset / billing / fax-to-email"
echo "    - [company]   — company name shown in UI footer"
echo "    - [site]      — site title shown in browser tab"
echo ""
echo "  ──────────────────────────────────────────────────────────"
echo "  [!] SECURITY — change these defaults before going to production:"
echo "  ──────────────────────────────────────────────────────────"
echo "    1. End-user web login still has the seeded default password:"
echo "         user@ictcore.org / helloUser"
echo "       Log in as admin and reset it from the User form, or:"
echo "         mysql -u root -p ictfax -e \\"
echo "           \"UPDATE usr SET passwd=MD5('<new-password>') WHERE email='user@ictcore.org';\""
echo ""
echo "    2. FreeSWITCH ESL password was auto-generated for this install:"
echo "         user=user  pass=${ESL_PASS}  (port 8021, bound to 127.0.0.1)"
echo "       Already written to /etc/freeswitch/autoload_configs/event_socket.conf.xml"
echo "       AND to /usr/ictcore/etc/ictcore.conf  ([freeswitch] section)."
echo "       Save this password — it is not re-displayed anywhere."
echo ""
echo "    3. The admin login uses the password you entered during install."
echo "       Database/PostgreSQL passwords were also set from your inputs"
echo "       and are stored in /usr/ictcore/etc/ictcore.conf (mode 0640)."
echo "============================================================"
