#!/usr/bin/env bash
#
# rebrand-theme.sh — give each domain install its own theme identity.
#
# Run from the theme root (the folder holding style.css and functions.php).
# Rewrites, in place:
#   * style.css header: Theme Name, Author, Description, Text Domain
#   * the gettext text domain in every PHP file
#   * the front-end CSS/JS/HTML prefix (classes, ids, handles, CSS variables,
#     JS globals, storage keys) and RODOLFO_PREFIX in functions.php
#   * the accent colour (+ derived hover/focus shades) and the font stacks
#   * optionally (--deep) every PHP function/constant/option prefix
#   * finally, the theme folder itself is renamed to the new slug
#
# Usage:
#   ./rebrand-theme.sh --slug northline --name "Northline" --prefix nl \
#       --accent "#0f766e" [--sans '"Segoe UI",Roboto,sans-serif'] \
#       [--serif 'Georgia,serif'] [--author "Example Media"] \
#       [--deep] [--no-rename] [--dry-run]
#
#   --slug      new folder name and text domain (lowercase letters, digits, hyphens)
#   --name      Theme Name shown in Appearance → Themes (default: slug, title-cased)
#   --prefix    2-6 lowercase letters for CSS classes / ids / handles (default: first 2-4 of slug)
#   --accent    #rrggbb brand colour; hover/focus shades are derived from it
#   --sans      replacement for the system sans-serif font stack
#   --serif     replacement for the display serif font stack
#   --author    Author header (default: unchanged)
#   --deep      also rename PHP function/constant/option prefixes
#               (use only on a NEW install: option names change, so saved
#               settings from the old prefix are not carried over)
#   --no-rename leave the folder name alone
#   --dry-run   report what would change, write nothing
#
# Requires: bash, perl. Safe to run once per install; run it again with the
# CURRENT identifiers if you ever need to rebrand a second time.

set -euo pipefail

die() { echo "error: $*" >&2; exit 1; }

# --- current identity (what we are replacing) --------------------------------
OLD_SLUG="rodolfo"
OLD_PREFIX="tk"
OLD_ACCENT="#3d54d6"
OLD_ACCENT_700="#2f42b5"
OLD_ACCENT_500="#5566e0"
OLD_ACCENT_400="#8b97ef"
OLD_SANS='system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif'
OLD_SERIF='"Iowan Old Style","Palatino Linotype","Book Antiqua",Palatino,Georgia,"Times New Roman",serif'

SLUG="" NAME="" PREFIX="" ACCENT="" SANS="" SERIF="" AUTHOR="" DEEP=0 RENAME=1 DRY=0

while [ $# -gt 0 ]; do
  case "$1" in
    --slug)      SLUG="${2:-}"; shift 2 ;;
    --name)      NAME="${2:-}"; shift 2 ;;
    --prefix)    PREFIX="${2:-}"; shift 2 ;;
    --accent)    ACCENT="${2:-}"; shift 2 ;;
    --sans)      SANS="${2:-}"; shift 2 ;;
    --serif)     SERIF="${2:-}"; shift 2 ;;
    --author)    AUTHOR="${2:-}"; shift 2 ;;
    --deep)      DEEP=1; shift ;;
    --no-rename) RENAME=0; shift ;;
    --dry-run)   DRY=1; shift ;;
    -h|--help)   sed -n '2,40p' "$0" | sed 's/^# \{0,1\}//'; exit 0 ;;
    *)           die "unknown option: $1 (try --help)" ;;
  esac
done

[ -f style.css ] && [ -f functions.php ] || die "run this from the theme root (style.css / functions.php not found)"
command -v perl >/dev/null 2>&1 || die "perl is required"
[ -n "$SLUG" ] || die "--slug is required"

[[ "$SLUG" =~ ^[a-z][a-z0-9-]{1,38}[a-z0-9]$ ]] || die "--slug must be lowercase letters, digits and hyphens (3-40 chars, starting with a letter)"
[ "$SLUG" != "$OLD_SLUG" ] || die "--slug must differ from the current slug ($OLD_SLUG)"

if [ -z "$PREFIX" ]; then
  PREFIX="$(printf '%s' "$SLUG" | tr -d '0-9-' | cut -c1-3)"
fi
[[ "$PREFIX" =~ ^[a-z]{2,6}$ ]] || die "--prefix must be 2-6 lowercase letters"
[ "$PREFIX" != "$OLD_PREFIX" ] || die "--prefix must differ from the current prefix ($OLD_PREFIX)"
case "$PREFIX" in
  wp|is|js|ui|my|do|if|on|no|id|a|b|p|h|x|ms|gb|rb) die "--prefix '$PREFIX' collides with WordPress or HTML conventions; pick another" ;;
esac

if [ -z "$NAME" ]; then
  NAME="$(printf '%s' "$SLUG" | tr '-' ' ' | perl -pe 's/\b(\w)/\U$1/g')"
fi

if [ -n "$ACCENT" ]; then
  [[ "$ACCENT" =~ ^#[0-9a-fA-F]{6}$ ]] || die "--accent must be #rrggbb"
  ACCENT="$(printf '%s' "$ACCENT" | tr 'A-F' 'a-f')"
fi

FN_PREFIX="$SLUG"; FN_PREFIX="${FN_PREFIX//-/_}"
FN_UPPER="$(printf '%s' "$FN_PREFIX" | tr 'a-z' 'A-Z')"

# Files we touch: text sources only, never .git or binaries.
mapfile -t FILES < <(find . -type f \( -name '*.php' -o -name '*.css' -o -name '*.js' -o -name '*.txt' -o -name '*.sh' \) -not -path './.git/*' -not -name 'rebrand-theme.sh' | sort)
mapfile -t PHP_FILES < <(find . -type f -name '*.php' -not -path './.git/*' | sort)

say() { printf '%s\n' "$*"; }
run_perl() { # run_perl <expr> <files...> — in-place edit unless --dry-run
  local expr="$1"; shift
  if [ "$DRY" -eq 1 ]; then return 0; fi
  perl -pi -e "$expr" "$@"
}

# Mix a hex colour toward black (negative) or white (positive), integer maths.
# mix <#rrggbb> <percent -100..100>
mix() {
  local hex="${1#\#}" pct="$2" out="#" i c
  for i in 0 2 4; do
    c=$((16#${hex:$i:2}))
    if [ "$pct" -ge 0 ]; then c=$(( c + (255 - c) * pct / 100 )); else c=$(( c * (100 + pct) / 100 )); fi
    [ "$c" -lt 0 ] && c=0; [ "$c" -gt 255 ] && c=255
    out+="$(printf '%02x' "$c")"
  done
  printf '%s' "$out"
}

say "Rebranding: $OLD_SLUG -> $SLUG  (name: $NAME, css prefix: $OLD_PREFIX -> $PREFIX)"
[ "$DRY" -eq 1 ] && say "(dry run — nothing will be written)"

# --- 1. style.css header ------------------------------------------------------
if [ "$DRY" -eq 0 ]; then
  NAME="$NAME" SLUG="$SLUG" AUTHOR="$AUTHOR" perl -pi -e '
    BEGIN { $n = $ENV{NAME}; $s = $ENV{SLUG}; $a = $ENV{AUTHOR}; }
    s/^Theme Name:.*/Theme Name: $n/;
    s/^Text Domain:.*/Text Domain: $s/;
    s/^Author:.*/Author: $a/ if length $a;
    s/^Author URI:.*\n// if length $a;
    s/^Theme URI:.*\n//;
  ' style.css
fi
say " - style.css header updated"

# --- 2. text domain in PHP ------------------------------------------------------
run_perl "s/'${OLD_SLUG}'/'${SLUG}'/g; s/\"${OLD_SLUG}\"/\"${SLUG}\"/g; s/'${OLD_SLUG}\//'${SLUG}\//g; s/'${OLD_SLUG}-/'${SLUG}-/g" "${PHP_FILES[@]}"
say " - text domain '$OLD_SLUG' -> '$SLUG' in ${#PHP_FILES[@]} PHP files"

# --- 3. front-end prefix --------------------------------------------------------
# tk-foo (classes, ids, handles, --tk-* variables) and the camelCase / snake keys.
CAP="$(printf '%s' "$PREFIX" | perl -pe 's/^(\w)/\U$1/')"
run_perl "s/(?<![A-Za-z0-9_])${OLD_PREFIX}-/${PREFIX}-/g;
          s/(?<![A-Za-z0-9_])${OLD_PREFIX}L10n/${PREFIX}L10n/g;
          s/(?<![A-Za-z0-9_])${OLD_PREFIX}EmbedAllow/${PREFIX}EmbedAllow/g;
          s/(?<![A-Za-z0-9_])${OLD_PREFIX}RecentSearches/${PREFIX}RecentSearches/g;
          s/(?<![A-Za-z0-9_])${OLD_PREFIX}_bookmarks/${PREFIX}_bookmarks/g;
          s/(?<![A-Za-z0-9_])${OLD_PREFIX}:resume:v1/${PREFIX}:resume:v1/g" "${FILES[@]}"
run_perl "s/define\( 'RODOLFO_PREFIX', '${OLD_PREFIX}' \)/define( 'RODOLFO_PREFIX', '${PREFIX}' )/" functions.php
say " - css/js prefix '$OLD_PREFIX-' -> '$PREFIX-' in ${#FILES[@]} files"

# --- 4. accent colour -----------------------------------------------------------
if [ -n "$ACCENT" ]; then
  A700="$(mix "$ACCENT" -22)"; A500="$(mix "$ACCENT" 14)"; A400="$(mix "$ACCENT" 36)"
  run_perl "s/\Q${OLD_ACCENT}\E/${ACCENT}/gi; s/\Q${OLD_ACCENT_700}\E/${A700}/gi; s/\Q${OLD_ACCENT_500}\E/${A500}/gi; s/\Q${OLD_ACCENT_400}\E/${A400}/gi" "${FILES[@]}"
  say " - accent $OLD_ACCENT -> $ACCENT (hover $A700, focus $A500, light $A400)"
fi

# --- 5. font stacks (values passed via the environment: no shell-quoting hazards)
if [ -n "$SANS" ] && [ "$DRY" -eq 0 ]; then
  OLD="$OLD_SANS" NEW="$SANS" perl -pi -e 'BEGIN{$o=$ENV{OLD};$n=$ENV{NEW}} s/\Q$o\E/$n/g' "${FILES[@]}"
  say " - sans-serif stack replaced"
fi
if [ -n "$SERIF" ] && [ "$DRY" -eq 0 ]; then
  OLD="$OLD_SERIF" NEW="$SERIF" perl -pi -e 'BEGIN{$o=$ENV{OLD};$n=$ENV{NEW}} s/\Q$o\E/$n/g' "${FILES[@]}"
  say " - display serif stack replaced"
fi

# --- 6. PHP identifier prefix (optional) -------------------------------------------
if [ "$DEEP" -eq 1 ]; then
  run_perl "s/(?<![A-Za-z0-9])rodolfo_/${FN_PREFIX}_/g; s/(?<![A-Za-z0-9])RODOLFO_/${FN_UPPER}_/g; s/\bRodolfo\b/${NAME}/g" "${PHP_FILES[@]}"
  # JS and SVG assets reference the same option / field names and the editor block namespace.
  JS_FILES=(); while IFS= read -r f; do JS_FILES+=("$f"); done < <(find assets -type f \( -name '*.js' -o -name '*.svg' \))
  if [ "${#JS_FILES[@]}" -gt 0 ]; then
    run_perl "s/(?<![A-Za-z0-9])rodolfo_/${FN_PREFIX}_/g; s/\"rodolfo\/([a-z-]+)\"/\"${SLUG}\/\$1\"/g; s/\"rodolfo\"/\"${SLUG}\"/g; s/\bRodolfo\b/${NAME}/g" "${JS_FILES[@]}"
  fi
  run_perl "s/\bRodolfo\b/${NAME}/g" readme.txt
  say " - PHP identifiers rodolfo_ -> ${FN_PREFIX}_ (deep rename; option names changed)"
else
  JS_FILES=(); while IFS= read -r f; do JS_FILES+=("$f"); done < <(find assets -type f \( -name '*.js' -o -name '*.svg' \))
  if [ "${#JS_FILES[@]}" -gt 0 ]; then
    run_perl "s/\"rodolfo\/([a-z-]+)\"/\"${SLUG}\/\$1\"/g; s/\"rodolfo\"/\"${SLUG}\"/g; s/\bRodolfo\b/${NAME}/g" "${JS_FILES[@]}"
  fi
  run_perl "s/\bRodolfo Berrios' blog \(https:\/\/rodolfoberrios.com\/\)/the design brief/; s/\bRodolfo\b/${NAME}/g" readme.txt
fi

# --- 7. sanity check ---------------------------------------------------------------
if [ "$DRY" -eq 0 ]; then
  LEFT="$( { grep -rIl --exclude-dir=.git --exclude=rebrand-theme.sh -i "rodolfo" . 2>/dev/null || true; } | wc -l | tr -d ' ')"
  say " - files still mentioning 'rodolfo' (internal function names unless --deep): $LEFT"
  if command -v php >/dev/null 2>&1; then
    BAD=0
    for f in "${PHP_FILES[@]}"; do php -l "$f" >/dev/null 2>&1 || { say "   syntax error: $f"; BAD=1; }; done
    [ "$BAD" -eq 0 ] && say " - php -l: all ${#PHP_FILES[@]} files OK"
  fi
fi

# --- 8. rename the folder ------------------------------------------------------------
if [ "$RENAME" -eq 1 ] && [ "$DRY" -eq 0 ]; then
  HERE="$(pwd)"; BASE="$(basename "$HERE")"; PARENT="$(dirname "$HERE")"
  if [ "$BASE" != "$SLUG" ]; then
    [ ! -e "$PARENT/$SLUG" ] || die "$PARENT/$SLUG already exists; not renaming (rerun with --no-rename)"
    mv "$HERE" "$PARENT/$SLUG"
    say " - folder renamed: $BASE -> $SLUG  (now at $PARENT/$SLUG)"
  fi
fi

say "Done. Activate the theme, then re-save Appearance → Theme Options once."
