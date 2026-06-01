# Payment Service API (Laravel)

A robust REST API for processing payments, featuring external gateway simulation and asynchronous webhook handling.

## 🚀 Key Features

* **REST API** for creating and retrieving payments.
* **Fake Gateway:** Emulation of an external payment gateway provider.
* **Asynchronous Webhooks:** Background webhook processing using Laravel Queues.
* **Clean Architecture:** Strict separation of concerns (Controller, Service, GatewayClient).

## 🛠 Tech Stack

* PHP 8.1+
* Laravel 10 / 11
* MySQL
* Laravel Queues (Database driver)

## ⚙️ Installation & Setup

1. Clone the repository and install dependencies:
```bash
git clone [https://github.com/RuslanBaatyrbekov/Api-project.git](https://github.com/RuslanBaatyrbekov/Api-project.git)
cd Api-project
composer install
```

2. Configure environment variables:
```bash
cp .env.example .env
php artisan key:generate
```

3. Update your `.env` file with the following database and queue settings:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel_payments
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=database
```

4. Run database migrations:
```bash
php artisan migrate
```

## 🚀 Running the Application

To run the application locally, you will need to open three separate terminal windows:

**Terminal 1:** Start the main application server
```bash
php artisan serve --port=8000
```

**Terminal 2:** Start the simulated Payment Gateway server
```bash
php artisan serve --port=8001
```

**Terminal 3:** Start the queue worker for webhook processing
```bash
php artisan queue:work
```

## 📡 API Endpoints Usage

### 1. Create a Payment
```http
POST [http://127.0.0.1:8000/api/payments](http://127.0.0.1:8000/api/payments)
```
**Payload:**
```json
{ 
    "amount": 1000.00, 
    "currency": "USD", 
    "description": "Test Order #123" 
}
```

### 2. Check Payment Status
Wait 2-3 seconds for the asynchronous webhook to process, then run:
```http
GET [http://127.0.0.1:8000/api/payments/](http://127.0.0.1:8000/api/payments/){id}
```
