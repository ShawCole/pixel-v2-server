#!/usr/bin/env bash
set -euo pipefail
if [[ $# -lt 1 ]]; then echo "Usage: $0 CLIENT_DB_NAME" >&2; exit 2; fi
CLIENT_DB_NAME="$1"
SCHEMA="$(dirname "$0")/../migrations/001_initial_schema.sql"
ENV_FILE="/var/www/pixel-v2/.env"
if [[ -f "$ENV_FILE" ]]; then set -o allexport; source "$ENV_FILE"; set +o allexport; fi
: "${DB_HOST:?missing}"; : "${DB_USER:?missing}"; : "${DB_PASS:?missing}"
export MYSQL_PWD="$DB_PASS"
sed "s/CLIENT_DB_NAME/${CLIENT_DB_NAME}/g" "$SCHEMA" | mysql --protocol=TCP -h "$DB_HOST" -u "$DB_USER"
unset MYSQL_PWD
echo "Applied schema for ${CLIENT_DB_NAME}"
