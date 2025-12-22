#!/bin/bash

echo "Starting Luxe Fashion Development Server..."
echo ""
echo "Starting MySQL container..."
docker start luxe_mysql_test
sleep 2

echo "MySQL container started"
echo ""
php -S 0.0.0.0:8000
