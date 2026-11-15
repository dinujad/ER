#!/bin/bash
# Run on the server after extracting the deploy zip (cPanel Terminal or SSH).
set -e
cd "$(dirname "$0")/.." || exit 1

echo "Setting directory permissions to 755..."
find . -type d -not -path './.git/*' -exec chmod 755 {} \;

echo "Setting file permissions to 644..."
find . -type f -not -path './.git/*' -exec chmod 644 {} \;

echo "Making storage and bootstrap/cache writable (775)..."
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

echo "Done. storage/ and bootstrap/cache/ are writable; everything else is 755/644."
