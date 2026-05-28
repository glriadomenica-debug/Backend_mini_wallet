# Mini Wallet Backend

Backend API for Mini Wallet System built using Laravel 12 and Laravel Sanctum.

---

# Overview

Mini Wallet backend provides secure REST API services for:

- Authentication
- Wallet management
- Top up transactions
- Money transfers
- Transaction history

This backend uses token authentication with Laravel Sanctum.

---

# Tech Stack

- Laravel 12
- PHP 8+
- MySQL
- Laravel Sanctum
- Eloquent ORM

---

# Features

## Authentication
- Register
- Login
- Sanctum Token Authentication
- Logout token system

## Wallet
- Automatic wallet creation after registration
- Check balance
- Update balance

## Top Up
- Add balance to wallet
- Save transaction history

## Transfer
- Transfer money to another user
- Validate username and phone number
- Prevent self transfer
- Prevent insufficient balance

## Transaction History
- Incoming transaction
- Outgoing transaction
- Top up history
- Transaction timestamps

---

# Installation

Clone repository:

```bash
git clone https://github.com/glriadomenica-debug/Backend_mini_wallet.git
```

Go to project folder:

```bash
cd Backend_mini_wallet
```

Install dependencies:

```bash
composer install
```

Copy environment file:

```bash
cp .env.example .env
```

Generate app key:

```bash
php artisan key:generate
```

---

# Database Setup

Create database:

```sql
CREATE DATABASE mini_wallet;
```

Update `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mini_wallet
DB_USERNAME=root
DB_PASSWORD=
```

---

# Migration

Run migration:

```bash
php artisan migrate
```

---

# Install Sanctum

```bash
php artisan install:api
```

Run migration again:

```bash
php artisan migrate
```

---

# Run Server

```bash
php artisan serve
```

Backend server will run on:

```bash
http://localhost:8000
```

---

# Database Tables

## users
Store user data.

## wallets
Store wallet balance.

## transactions
Store transaction history.

## personal_access_tokens
Store Sanctum tokens.

---

# API Endpoints

# Authentication

| Method | Endpoint | Description |
|---|---|---|
| POST | /api/auth/register | Register new user |
| POST | /api/auth/login | Login user |

---

# Wallet

| Method | Endpoint | Description |
|---|---|---|
| GET | /api/balance | Get wallet balance |
| POST | /api/topup | Top up balance |

---

# Transaction

| Method | Endpoint | Description |
|---|---|---|
| POST | /api/transfer | Transfer money |
| GET | /api/transactions | Get transaction history |

---

# Authentication Header

Protected routes require Bearer Token.

Example:

```bash
Authorization: Bearer your_token
```

---

# Example Login Response

```json
{
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "username": "Gloria",
      "email": "gloria@gmail.com"
    },
    "token": "1|xxxxxxxxxxxx"
  }
}
```

---

# Transfer Validation

Transfer system validates:
- Username exists
- Phone number exists
- Receiver exists
- Cannot transfer to self
- Sufficient balance

---

# Transaction Logic

## Top Up
- Increase wallet balance
- Create topup transaction

## Transfer
- Sender balance decreases
- Receiver balance increases
- Create transfer transaction

---

# API Testing

Recommended tools:
- Postman
- Thunder Client

---

# Future Improvements

- Refresh token
- Email verification
- QR transfer
- Transaction pagination
- Admin dashboard
- Notification system

---

# Author

Gloria Domenica

GitHub:
https://github.com/glriadomenica-debug
