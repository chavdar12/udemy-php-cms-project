#!/usr/bin/env bash
# CI for this repo, run from its root by .github/workflows/ci.yml:
#   bash ci.sh build php
#   bash ci.sh format php-cs-fixer
# Optional env (set in the workflow): SETUP
set -uo pipefail
mode=$1; shift
TMP=${RUNNER_TEMP:-$(mktemp -d)}
group() { [ -n "${GITHUB_ACTIONS:-}" ] && echo "::group::$1" || echo "== $1"; }
endgroup() { [ -n "${GITHUB_ACTIONS:-}" ] && echo "::endgroup::" || true; }
fail() { echo "${GITHUB_ACTIONS:+::error::}$1 failed"; status=1; }
status=0

if [ -n "${SETUP:-}" ] && [ "$mode" = build ]; then RUNNER_TEMP=$TMP bash -ec "$SETUP" || exit 1; fi

for kind in "$@"; do case "$mode:$kind" in
build:php)
  git ls-files -z '*.php' | xargs -0 -r -n1 php -l > /dev/null || fail "php -l"
  # Laravel apps (composer.json + artisan) run their own suite; their tests are not plain scripts
  apps=$(git ls-files '*artisan' | xargs -r -n1 dirname | while read -r d; do [ -f "$d/composer.json" ] && echo "$d"; done)
  for dir in $apps; do
    group "$dir"
    (cd "$dir" && composer install --no-interaction --no-progress -q && { [ -f .env ] || cp .env.example .env; } \
      && php artisan test) || fail "$dir"
    endgroup
  done
  for t in $(git ls-files '*test_*.php' '*Test.php'); do
    for app in $apps; do case "$t" in "$app"/*) continue 2;; esac; done
    group "$t"; (cd "$(dirname "$t")" && php "$(basename "$t")") || fail "$t"; endgroup
  done ;;
format:php-cs-fixer)
  [ -f "$TMP/php-cs-fixer.phar" ] || curl -sSL -o "$TMP/php-cs-fixer.phar" https://github.com/PHP-CS-Fixer/PHP-CS-Fixer/releases/download/v3.95.27/php-cs-fixer.phar
  php "$TMP/php-cs-fixer.phar" check --diff || fail php-cs-fixer ;;
*) echo "unknown $mode kind: $kind" >&2; exit 2 ;;
esac; done
exit $status
