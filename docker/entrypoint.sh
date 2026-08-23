#!/bin/bash
set -e

echo "Forcing a single Apache MPM (mpm_prefork)."
a2dismod -f mpm_event >/dev/null 2>&1 || true
a2dismod -f mpm_worker >/dev/null 2>&1 || true
a2enmod mpm_prefork >/dev/null 2>&1 || true
ls -1 /etc/apache2/mods-enabled/ | grep -E '^mpm_' || true

: "${PORT:=80}"
sed -ri "s/^Listen 80$/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

APP_URL="${APP_BASE_URL:-http://localhost:${PORT}/}"
[[ "${APP_URL}" != */ ]] && APP_URL="${APP_URL}/"

cat > /var/www/html/.env <<ENV
CI_ENVIRONMENT = ${CI_ENVIRONMENT:-production}

app.baseURL = '${APP_URL}'
app.indexPage = ''
app.forceGlobalSecureRequests = ${APP_FORCE_HTTPS:-false}
app.sessionDriver = 'CodeIgniter\Session\Handlers\FileHandler'
app.sessionCookieName = 'crm_session'
app.sessionExpiration = 7200
app.sessionSavePath = null
app.CSRFProtection = false

database.default.hostname = ${DB_HOST:-localhost}
database.default.database = ${DB_NAME:-legacy_crm}
database.default.username = ${DB_USER:-root}
database.default.password = ${DB_PASS:-}
database.default.DBDriver = MySQLi
database.default.DBPrefix =
database.default.port = ${DB_PORT:-3306}

jwt.secret = '${JWT_SECRET:?JWT_SECRET must be set}'
jwt.ttl = ${JWT_TTL:-3600}

email.enabled = ${EMAIL_ENABLED:-false}
email.fromEmail = '${EMAIL_FROM:-no-reply@legacy-crm.test}'
email.fromName = 'Legacy CRM'
email.protocol = 'smtp'
email.SMTPHost = '${SMTP_HOST:-}'
email.SMTPUser = '${SMTP_USER:-}'
email.SMTPPass = '${SMTP_PASS:-}'
email.SMTPPort = ${SMTP_PORT:-2525}
email.SMTPCrypto = '${SMTP_CRYPTO:-tls}'
ENV

chown www-data:www-data /var/www/html/.env

echo "Waiting for database at ${DB_HOST}:${DB_PORT:-3306} ..."
for i in $(seq 1 30); do
    if php -r '
        $c = @mysqli_connect(getenv("DB_HOST"), getenv("DB_USER"), getenv("DB_PASS"), getenv("DB_NAME"), (int) (getenv("DB_PORT") ?: 3306));
        exit($c ? 0 : 1);
    ' 2>/dev/null; then
        echo "Database is up."
        break
    fi
    sleep 2
done

php spark migrate --all || echo "Migrations failed - check the logs."

if [ "${RUN_SEED:-false}" = "true" ]; then
    EXISTING=$(php -r '
        $c = @mysqli_connect(getenv("DB_HOST"), getenv("DB_USER"), getenv("DB_PASS"), getenv("DB_NAME"), (int) (getenv("DB_PORT") ?: 3306));
        if (! $c) { echo "-1"; exit; }
        $r = @mysqli_query($c, "SELECT COUNT(*) AS c FROM customers");
        echo $r ? (int) mysqli_fetch_assoc($r)["c"] : -1;
    ' 2>/dev/null)

    if [ "${EXISTING:-0}" -gt 0 ]; then
        echo "Customers already present (${EXISTING}), skipping seed."
    else
        echo "Seeding..."
        php spark db:seed DatabaseSeeder || true
        php spark db:seed UserSeeder || true
    fi
fi

php spark cache:clear || true

exec "$@"
