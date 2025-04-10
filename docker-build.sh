#!/bin/bash

docker-compose down -v

docker-compose up --no-start --build

docker-compose up --build --wait postgres

docker-compose exec -e PGPASSWORD=root postgres psql -U root -d test -f /var/project/sql/DDL.sql
docker-compose exec -e PGPASSWORD=root postgres psql -U root -d test -f /var/project/sql/insert.sql

docker-compose start
