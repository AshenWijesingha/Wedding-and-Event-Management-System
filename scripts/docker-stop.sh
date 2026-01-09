#!/bin/bash

# EventPro Docker Stop Script
# This script stops all Docker containers

echo "🛑 Stopping EventPro Docker containers..."

docker compose down

echo "✅ Docker containers stopped successfully!"
echo ""
echo "💡 To start again, run: ./scripts/docker-start.sh"
echo "💡 To remove volumes, run: docker compose down -v"
