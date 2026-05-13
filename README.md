# BIG 4 Restaurant Management Platform

[![Symfony](https://img.shields.io/badge/Symfony-6.4-black?logo=symfony)](https://symfony.com/)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-777bb4?logo=php&logoColor=white)](https://www.php.net/)
[![Doctrine](https://img.shields.io/badge/Doctrine-ORM%20%2B%20DBAL-2f4f4f)](https://www.doctrine-project.org/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952b3?logo=bootstrap&logoColor=white)](https://getbootstrap.com/)
[![Webpack Encore](https://img.shields.io/badge/Webpack%20Encore-Asset%20Build-1f72b6?logo=webpack)](https://symfony.com/doc/current/frontend/encore.html)
[![FastAPI](https://img.shields.io/badge/FastAPI-AI%20Service-009688?logo=fastapi&logoColor=white)](https://fastapi.tiangolo.com/)

## Description

BIG 4 is a Symfony 6.4 business application for restaurant and operations management. The codebase combines administration, sales, and logistics workflows in one place, including menu and dish management, orders, reservations, table management, delivery tracking, food donation management, waste tracking, user/profile administration, and AI-assisted operational tools.

The repository also includes two supporting services:

- a small WebSocket server for live delivery tracking
- a FastAPI-based AI feedback service for classifying delivery reviews and routing them to testimonials or support

Main features visible in the codebase include:

- Admin dashboard and analytics pages
- Menu, dish, recipe, and ingredient management
- Order, reservation, and restaurant table workflows
- Delivery driver and live location tracking
- Fleet, waste, and sustainability modules
- Food donation events and donation item management
- Google OAuth login support
- AI stock assistant / conversational helper
- PDF generation and notification-related services

## Tech Stack

- PHP 8.1+
- Symfony 6.4
- Doctrine ORM, Doctrine DBAL, and Doctrine Migrations
- Twig templates
- Bootstrap 5
- Symfony UX Stimulus and Turbo
- Webpack Encore, Babel, Sass, PostCSS, and Asset Mapper/importmap
- KnpPaginatorBundle
- KnpU OAuth2 Client Bundle
- Google OAuth client libraries
- Dompdf
- Monolog
- PHPUnit and PHPStan
- Node.js tooling for frontend assets
- Express and ws for the realtime WebSocket server
- Python FastAPI, Uvicorn, SQLAlchemy, PyMySQL, google-genai, and python-dotenv for `ai_feedback/`
- MySQL / MariaDB is configured in the current `.env`; PostgreSQL is also referenced in `compose.yaml`

## Prerequisites

Install the following before running the project locally:

- PHP 8.1 or newer
- Composer
- Node.js 14+ and npm
- A database server compatible with your `DATABASE_URL` value
- Python 3.x if you want to run the AI feedback service in `ai_feedback/`
- Symfony CLI is recommended for local development, but not strictly required

## Installation

```bash
git clone https://github.com/ZAYNEBelaifi/pi-dev-project.git
cd FirstProject
composer install
npm install
```

If you are using the AI feedback service:

```bash
cd ai_feedback
pip install -r requirements.txt
cd ..
```

If you want to run the realtime delivery WebSocket server:

```bash
cd realtime-server
npm install
cd ..
```

## Configuration

The main Symfony application reads environment variables from `.env`, `.env.local`, and the other standard Symfony dotenv layers.

Important values currently present in the repository include:

- `APP_ENV`
- `APP_SECRET`
- `DATABASE_URL`
- `MESSENGER_TRANSPORT_DSN`
- `MAILER_DSN`
- `GOOGLE_CLIENT_ID`
- `GOOGLE_CLIENT_SECRET`
- `AI_STOCK_API_URL`
- `AI_STOCK_MODEL`
- `AI_STOCK_API_KEY`
- `FREE_CHAT_API_URL`
- `FREE_CHAT_TIMEOUT`
- `WEATHER_LATITUDE`
- `WEATHER_LONGITUDE`

Current database configuration in `.env` points to MySQL / MariaDB:

```bash
DATABASE_URL="mysql://root:@127.0.0.1:3306/project?serverVersion=10.11.2-MariaDB&charset=utf8mb4"
```

Recommended setup steps:

```bash
cp .env .env.local
# edit .env.local with your own database, OAuth, and AI credentials
```

Then create and update the schema as needed:

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

## Usage

Start the main Symfony application:

```bash
symfony server:start
```

If you are not using the Symfony CLI, a PHP built-in server also works:

```bash
php -S 127.0.0.1:8000 -t public
```

Build or watch frontend assets:

```bash
npm run dev
npm run watch
npm run build
```

Run the Messenger worker used by the project:

```bash
php bin/console messenger:consume async -vv
```

Run the AI feedback API:

```bash
cd ai_feedback
uvicorn delivery_feedback_ai:app --reload --host 127.0.0.1 --port 8001
```

Run the realtime WebSocket server:

```bash
cd realtime-server
npm start
```

On Windows, the repository includes `start-dev.ps1` to launch the Symfony server, Messenger worker, and AI service in separate terminals.

## Project Structure

```text
.
├── ai_feedback/              # FastAPI-based AI feedback service
├── assets/                   # Frontend entrypoints, controllers, styles, and vendors
├── config/                   # Symfony bundle, route, service, and package configuration
├── database/                 # SQL schema merge and legacy database scripts
├── migrations/               # Doctrine migration files
├── public/                   # Web root and compiled assets
├── realtime-server/          # Node.js WebSocket server for live tracking
├── src/                      # Symfony controllers, entities, services, repositories, listeners
├── templates/                # Twig templates for frontend and admin UI
├── tests/                    # PHPUnit tests
├── translations/             # Translation files
└── var/                      # Cache and logs
```

Key backend areas visible in `src/` include:

- `Controller/` for admin, delivery, donation, reservation, order, and auth flows
- `Entity/` for Doctrine entities such as `Delivery`, `DeliveryMan`, `Menu`, `Order`, `Reservation`, `FoodDonationEvent`, `FoodDonationItem`, `User`, and related models
- `Service/` for business logic, delivery orchestration, AI helpers, and notifications
- `Repository/` for database access

## Contributors

The git history in this workspace lists the following contributors:

- Mouadh Farhat - GitHub profile not exposed in repository metadata
- ZAYNEBelaifi - https://github.com/ZAYNEBelaifi
- Nada770 - GitHub profile not exposed in repository metadata
- wissem benali - GitHub profile not exposed in repository metadata
- 0205momo - GitHub profile not exposed in repository metadata
- Noura-14 - GitHub profile not exposed in repository metadata
- Dridi Emna - GitHub profile not exposed in repository metadata
- Nada Benrhouma - GitHub profile not exposed in repository metadata
- copilot-swe-agent[bot] - automated contributor

If you want a fully linked credits section, add the missing GitHub handles for the non-public contributors above.

