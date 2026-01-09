#!/bin/bash

# EventPro Docker Logs Script
# This script displays logs from Docker containers

SERVICE=${1:-app}

if [ "$SERVICE" = "all" ]; then
    echo "📋 Showing logs for all services..."
    docker compose logs -f
else
    echo "📋 Showing logs for $SERVICE..."
    echo "💡 Available services: app, mysql, redis, mailhog, meilisearch"
    echo "💡 Use 'all' to show logs for all services"
    echo ""
    docker compose logs -f $SERVICE
fi
