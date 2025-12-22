#!/bin/bash

echo "Stopping Luxe Fashion Development Server..."
echo ""

# Stop MySQL Docker container
echo "Stopping MySQL container..."
docker stop luxe_mysql

echo "MySQL container stopped"
echo ""
echo "Done! Server stopped."
