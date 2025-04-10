Реализован паттерн Data Mapper и также Identity Map

В папке sql находятся все sql скрипты:
- DDL.sql - DDL скрипт для создания таблицы пользователей
- insert.sql - скрипт наполнения данными

docker-build.sh - запускает docker контейнер с postgres и php

Написаны тесты для тестирования основных операций с БД. Запустить можно командой:
```bash
docker-compose exec php composer test
```