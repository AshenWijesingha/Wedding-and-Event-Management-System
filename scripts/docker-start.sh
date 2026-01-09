#!/bin/bash

# EventPro Docker Start Script
# This script starts all Docker containers for the development environment

echo "🚀 Starting EventPro Docker containers..."

# Build and start containers
docker-compose up -d --build

# Wait for services to be ready
echo "⏳ Waiting for services to be ready..."
sleep 10

# Check if containers are running
echo "📊 Container Status:"
docker-compose ps

echo ""
echo "✅ Docker containers started successfully!"
echo ""
echo "📌 Available Services:"
echo "   - Application: http://localhost:8000"
echo "   - Mailhog UI: http://localhost:8025"
echo "   - Meilisearch: http://localhost:7700"
echo "   - MySQL: localhost:3306"
echo "   - Redis: localhost:6379"
echo ""
echo "💡 Next steps:"
echo "   1. Run './scripts/docker-setup.sh' if this is your first time"
echo "   2. Run './scripts/docker-logs.sh' to view logs"
echo "   3. Run './scripts/docker-stop.sh' to stop containers"
