#!/usr/bin/env bash
set -euo pipefail
# Runs on GitHub's disposable CI runner only, not on a production site.
: "${RUNNER_TEMP:?CI runner required}"
: "${GITHUB_WORKSPACE:?CI workspace required}"
: "${WP_VERSION:?WordPress version required}"
export RODOLFO_CI_SMOKE=1
root="$RUNNER_TEMP/rodolfo-wp"
mkdir -p "$root"
cli="$RUNNER_TEMP/wp-cli.phar"
curl --fail --silent --show-error --location --retry 3 \
  https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o "$cli"
wp() { php "$cli" --path="$root" "$@"; }
wp core download --version="$WP_VERSION"
wp config create --dbname=wordpress --dbuser=wordpress --dbpass=ci-password --dbhost=127.0.0.1:3306
wp core install --url=http://127.0.0.1:8080 --title=Rodolfo-CI --admin_user=ci-admin \
  --admin_password=ci-only-admin-password --admin_email=ci@example.invalid --skip-email
mkdir -p "$root/wp-content/themes/rodolfo"
git -C "$GITHUB_WORKSPACE" archive HEAD | tar -x -C "$root/wp-content/themes/rodolfo"
wp theme activate rodolfo
wp eval-file "$GITHUB_WORKSPACE/tests/wp-smoke.php"
public_id="$(wp post list --post_type=post --name=rd-ci-public --field=ID)"
protected_id="$(wp post list --post_type=post --name=rd-ci-protected --field=ID)"
test -n "$public_id" && test -n "$protected_id"
php -S 127.0.0.1:8080 -t "$root" > "$RUNNER_TEMP/rodolfo-server.log" 2>&1 &
server=$!
cleanup() {
  kill "$server" 2>/dev/null || true
  cat "$RUNNER_TEMP/rodolfo-server.log"
}
trap cleanup EXIT
base=http://127.0.0.1:8080/
curl -fsS --retry 5 --retry-connrefused --max-time 20 "$base" -o "$RUNNER_TEMP/home.html"
curl -fsSL --max-time 20 "$base?p=$public_id" -o "$RUNNER_TEMP/public.html"
grep -Fq RD_CI_PUBLIC_BODY "$RUNNER_TEMP/public.html"
grep -Fq 'application/ld+json' "$RUNNER_TEMP/public.html"
curl -fsSL --max-time 20 "$base?p=$protected_id" -o "$RUNNER_TEMP/protected.html"
curl -fsSL --max-time 20 "$base?feed=rss2" -o "$RUNNER_TEMP/feed.xml"
curl -fsSL --max-time 20 "$base?rodolfo_llms=1" -o "$RUNNER_TEMP/llms.txt"
if grep -Eq 'RD_CI_PROTECTED_(BODY|EXCERPT)' "$RUNNER_TEMP/protected.html" "$RUNNER_TEMP/feed.xml" "$RUNNER_TEMP/llms.txt"; then
  echo "Protected content leaked" >&2
  exit 1
fi
grep -Fq RD_CI_PUBLIC_BODY "$RUNNER_TEMP/feed.xml"
grep -Fq rd-ci-public "$RUNNER_TEMP/llms.txt"
if grep -Eq 'rd-ci-protected|rd-ci-unlisted' "$RUNNER_TEMP/llms.txt"; then
  echo "Hidden post leaked into llms.txt" >&2
  exit 1
fi
curl -fsSL --max-time 20 "$base?rodolfo_card=$public_id" -o "$RUNNER_TEMP/card.png"
php -r '$i = getimagesize($argv[1]); exit($i && $i[0] === 1200 && $i[1] === 630 && $i[2] === IMAGETYPE_PNG ? 0 : 1);' "$RUNNER_TEMP/card.png"
status="$(curl -sS --max-time 20 -o /dev/null -w '%{http_code}' "$base?rodolfo_card=$protected_id")"
test "$status" = 404
echo "PASS: HTTP rendering, RSS protection, llms.txt visibility and social-card endpoint"
