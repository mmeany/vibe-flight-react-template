#!/usr/bin/env bash
set -euo pipefail

BASE="/app/"
RUN_TESTS=false
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

usage() {
    echo "Usage: $0 [--base PATH] [--run-tests]"
    echo
    echo "  --base PATH      Subdirectory base path for deployment (default: /scanner/)"
    echo "  --run-tests      Run frontend and backend tests before building"
    echo "  --help, -h       Show this help"
    exit 1
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --base)
            BASE="$2"
            shift 2
            ;;
        --run-tests)
            RUN_TESTS=true
            shift
            ;;
        --help|-h)
            usage
            ;;
        *)
            echo "ERROR: Unknown option: $1"
            usage
            ;;
    esac
done

# ensure trailing slash on base path
[[ "$BASE" != */ ]] && BASE="${BASE}/"

echo "=== Checking prerequisites ==="
for cmd in node npm php composer; do
    if ! command -v "$cmd" &>/dev/null; then
        echo "ERROR: '$cmd' is required but not found in PATH"
        exit 1
    fi
done
echo "All prerequisites found."

if $RUN_TESTS; then
    echo
    echo "=== Running frontend tests ==="
    (cd "$SCRIPT_DIR/frontend" && npm test)
    echo
    echo "=== Running backend tests ==="
    (cd "$SCRIPT_DIR/backend" && composer test)
fi

echo
echo "=== Cleaning dist/ ==="
rm -rf "$SCRIPT_DIR/dist"
mkdir -p "$SCRIPT_DIR/dist"

echo
echo "=== Building frontend (base=$BASE) ==="
(cd "$SCRIPT_DIR/frontend" && npm run build -- --base "$BASE")

echo
echo "=== Installing backend dependencies ==="
cp "$SCRIPT_DIR/backend/composer.json" "$SCRIPT_DIR/dist/"
cp "$SCRIPT_DIR/backend/composer.lock" "$SCRIPT_DIR/dist/"
(cd "$SCRIPT_DIR/dist" && composer install --no-dev --optimize-autoloader)

echo
echo "=== Copying backend source ==="
cp -r "$SCRIPT_DIR/backend/app" "$SCRIPT_DIR/dist/"

echo
echo "=== Copying frontend build output ==="
cp -r "$SCRIPT_DIR/frontend/dist/"* "$SCRIPT_DIR/dist/"
cp -r "$SCRIPT_DIR/frontend/public/"* "$SCRIPT_DIR/dist/"

echo
echo "=== Setting up PHP entry point ==="
cp "$SCRIPT_DIR/backend/public/index.php" "$SCRIPT_DIR/dist/"

echo
echo "=== Generating .htaccess ==="
cat > "$SCRIPT_DIR/dist/.htaccess" <<HTEOF
DirectoryIndex index.html

RewriteEngine On
RewriteBase $BASE

<Files ".env">
    Require all denied
</Files>

RewriteRule ^(?:app|vendor|logs)(?:/|$) - [F,L]

RewriteRule ^api/ index.php [L,QSA]

RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]

RewriteRule ^ index.html [L]

<ifmodule mod_deflate.c>
AddOutputFilterByType DEFLATE text/text text/html text/plain text/xml text/css application/x-javascript application/javascript
</ifmodule>

HTEOF

echo
echo "=== Copying .env.example ==="
cp "$SCRIPT_DIR/backend/.env.example" "$SCRIPT_DIR/dist/.env.example"

echo
echo "=== Creating logs directory ==="
mkdir -p "$SCRIPT_DIR/dist/logs"
chmod 755 "$SCRIPT_DIR/dist/logs"

echo
echo "=== Build complete ==="
echo "Output: $SCRIPT_DIR/dist/"
echo "Base path: $BASE"
