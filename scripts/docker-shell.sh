#!/bin/bash

# EventPro Docker Shell Script
# This script provides shell access to containers

SERVICE=${1:-app}

echo "🐚 Opening shell for $SERVICE..."
echo "💡 Available services: app, mysql, redis, mailhog, meilisearch"
echo ""

if [ "$SERVICE" = "mysql" ]; then
    docker compose exec mysql mysql -u root -psecret eventpro
else
    docker compose exec $SERVICE /bin/sh -c "[ -e /bin/bash ] && /bin/bash || /bin/sh"
fi
