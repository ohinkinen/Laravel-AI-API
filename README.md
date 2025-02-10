# Laravel API for local LLM chatbot

## Features

- User authentication (registration, login, logout, profile retrieval)

- Message sending to an Ollama run local LLM

## Installation

### Prerequisites

- PHP 8.3+

- Composer

- Laravel 11+

- Docker

- Ollama (for running the local LLM)

## Setup

### Running Ollama LLM locally

1. Install Ollama following the official instructions

2. Pull the model:
   ```
   ollama pull <model-name>
   ```
   Mistral is the default model expected by the API

3. When Ollama is running and model has been downloaded, Ollama should be ready to receive messages from the API

### Setting up the API

1. Clone the repository

2. Install dependencies:
   ```
   composer install
    
   npm install && npm run build
   ```

3. Create a Docker container for the MariaDB database and Adminer:
   ```
   docker compose up -d
   ```
   Container creates the database with these credentials:
   - Username: mariadb
   - Password: mariadb
   - Database: mariadb

4. Copy the environment file:
   ```
   cp .env.example .env
   ```
   Add the database credentials to the .env file so that the API can store user and chat history data

5. Generate the application key:
   ```
   php artisan key:generate
   ```

6. Run database migrations:
   ```
   php artisan migrate
   ```

7. Start the development server

## Configuration

Update the `model` property in the `ChatbotController.php` file if you want to change the LLM model (Mistral is the default model).

## API Endpoints

### Authentication

#### Register

Endpoint: `POST /api/register`

- Headers: `Accept: application/json`

- Request:
  ```
  {
    "name": "John Doe",
    "email": "johndoe@example.com",
    "password": "password",
    "password_confirmation": "password"
  }
  ```

- Response:
  ```
  {
    "message": "New user registered"
  }
  ```

#### Login

Endpoint: `POST /api/login`

- Headers: `Accept: application/json`

- Request:
  ```
  {
    "email": "johndoe@example.com",
    "password": "password"
  }
  ```

- Response:
  ```
  {
    "accessToken": "<Bearer-token>"
  }
  ```

#### Logout

Endpoint: `GET /api/logout`

- Headers: `Authorization: Bearer <Bearer-token>` and `Accept: application/json`

- Response:
  ```
  {
    "message": "Logged out"
  }
  ```

#### Get user profile

Endpoint: `GET /api/user`

- Headers: `Authorization: Bearer <Bearer-token>` and `Accept: application/json`

- Response:
  ```
  {
    "id": 1,
    "name": "John Doe",
    "email": "johndoe@example.com"
  }
  ```

### Chatbot

#### Send message (without authentication)

- Endpoint: `POST /api/chat`

- Headers: `Accept: application/json`

- Request:
  ```
  {
    "message": "Hello, how are you?"
  }
  ```

- Response:
  ```
  {
    "response": "I am an AI assistant. How can I help you today?"
  }
  ```

#### Send message (with authentication)

- Endpoint : `POST /api/chat`

- Headers: `Authorization: Bearer <Bearer-token>` and `Accept: application/json`

- Request:
  ```
  "message": "Let's continue our conversation",
  "session_id": "<session-UUID>"
  ```
  When the user is authenticated but the session id is missing, the API will generate a new session id for the conversation and store it in the database

- Response:
  ```
  "response": "Let's continue where we left off last time."
  "session_id": "<session-UUID>"
  ```