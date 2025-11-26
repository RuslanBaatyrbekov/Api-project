# Payment Service (Laravel)

## Особенности
- REST API для создания и просмотра платежей.
- Эмуляция внешнего платежного шлюза (Fake Gateway).
- Асинхронная обработка вебхуков через очереди (Laravel Queues).
- Обработка вебхуков.
- Разделение ответственности (Controller, Service, GatewayClient).

## Технологический стек
- PHP 8.1+
- Laravel 10/11
- MySQL
- Laravel Queues (Database driver)

## Установка и настройка

   git clone [https://github.com/RuslanBaatyrbekov/Api-project.git](https://github.com/RuslanBaatyrbekov/Api-project.git)
   cd Api-project
   composer install
   cp .env.example .env
   php artisan key:generate


   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=laravel_payments
   DB_USERNAME=root
   DB_PASSWORD=
   QUEUE_CONNECTION=database

   php artisan migrate

## Запуск
   -Первый термнал:
   php artisan serve --port=8000
   -Второй терминал для шлюза
   php artisan serve --port=8001
   -Третий терминал для обработки вебхука
   php artisan queue:work

## Создание платежа
   POST http://127.0.0.1:8000/api/payments
   {
   "amount": 1000.00,
   "currency": "USD",
   "description": "Test Order #123"
   }

## Проверка статуса (через 2-3 секунды)
   GET http://127.0.0.1:8000/api/payments/{id}
