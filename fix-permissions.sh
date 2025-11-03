#!/bin/bash
# Fix file permissions for routes/api.php
docker compose exec app chown -R www-data:www-data /var/www/html/routes
docker compose exec app chmod -R 664 /var/www/html/routes/api.php

