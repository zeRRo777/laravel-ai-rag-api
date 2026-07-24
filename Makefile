.PHONY: up down php art composer swagger

# Запуск проекта
up:
	docker compose up -d

# Остановка проекта
down:
	docker compose down

# Зайти в консоль PHP контейнера
php:
	docker compose exec php bash

# Выполнить artisan команду (пример: make art c="migrate")
art:
	docker compose exec php php artisan $(c)

# Выполнить composer команду (пример: make composer c="require guzzlehttp/guzzle")
composer:
	docker compose exec php composer $(c)

# Сгенерировать Swagger документацию
swagger:
	docker compose exec php php artisan l5-swagger:generate $(c)
