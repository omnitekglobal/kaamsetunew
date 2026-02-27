#!/bin/sh
# Start PHP API so all requests go through index.php (required for /api/* routes).
# Usage: ./run.sh   or   sh run.sh
cd "$(dirname "$0")"
echo "Starting PHP API at http://localhost:8000 (router enabled)"
exec php -S localhost:8000 router.php
