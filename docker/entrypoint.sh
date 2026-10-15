#!/bin/sh
# First boot on a fresh clone. .env, APP_KEY and database/database.sqlite are
# all gitignored, so without this the app dies with MissingAppKeyException.
# Every step is idempotent -- it runs on every container start.
set -e

[ -f .env ] || cp .env.example .env

grep -q '^APP_KEY=base64:' .env || php artisan key:generate --force

# ML_API_URL has to live in .env, not in compose `environment:`. `php artisan
# serve` only forwards a fixed list of variables to its php -S worker
# (ServeCommand::$passthroughVariables), so anything set in the container
# environment is invisible to the app.
grep -q '^ML_API_URL=' .env || printf '\nML_API_URL=http://ml:8001\n' >> .env

touch database/database.sqlite
php artisan migrate --force --quiet

exec "$@"