#!/bin/bash

# EventPro Docker Restart Script
# This script restarts Docker containers

echo "🔄 Restarting EventPro Docker containers..."

docker-compose restart

echo "✅ Docker containers restarted successfully!"
echo ""
echo "💡 To view logs, run: ./scripts/docker-logs.sh"
