# 🚀 CommerceSystem-API

[![Laravel 12](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel)](https://laravel.com)
[![PHP 8.2](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php)](https://www.php.net/)
[![Sanctum](https://img.shields.io/badge/Auth-Sanctum-brightgreen?style=for-the-badge)](https://laravel.com/docs/12.x/sanctum)
[![License](https://img.shields.io/badge/License-MIT-yellow.svg?style=for-the-badge)](https://opensource.org/licenses/MIT)

**CommerceSystem-API** is the powerful core engine driving a multi-tenant, modern commerce ecosystem. It provides robust API endpoints for both customer-facing storefronts (e.g., Next.js) and administrative interfaces (e.g., Angular POS/Admin), featuring real-time inventory synchronization and comprehensive ERP management.

---

## ✨ Key Features

### 🛒 Storefront API

Public-facing endpoints optimized for high-performance eCommerce experiences.

- **Product Discovery**: Paginated listing with category filtering.
- **Detailed Views**: Slug-based product identification for SEO-friendly URLs.

### 🔐 Admin & POS API

Highly secure endpoints protected by **Laravel Sanctum**, designed for internal management.

- **Full CRUD**: Manage Products, Categories, Branches, and Employees.
- **Stock Management**: Real-time stock level updates and historical logging.
- **Sales Flow**: Handle transactions, payments, and returns with precision.

### 📡 Real-time Sync

- **Broadcasting**: Instant inventory updates via WebSockets (Laravel Echo compatible).
- **Inventory Sync**: Automated synchronization between online storefronts and physical POS locations.

### 🏢 Comprehensive ERP

- **Multi-Branch Support**: Monitor and manage stock across various locations.
- **Supplier & Purchase Orders**: Track procurement and supply chain interactions.
- **Media Management**: Centralized handling of product images and assets.

---

## 🛠 Tech Stack

- **Framework**: [Laravel 12](https://laravel.com)
- **Authentication**: [Laravel Sanctum](https://laravel.com/docs/sanctum)
- **Database**: PostgreSQL / MySQL / SQLite (Agnostic)
- **Real-time**: Laravel Reverb / Pusher (Broadcasting)
- **Developer Tools**: Laravel Pail, Pint, and Sail

---

## 🚀 Getting Started

### Prerequisites

- PHP 8.2+
- Composer
- Node.js & NPM

### Express Installation

Use the built-in setup script to get everything ready in one go:

```bash
composer setup
```

_This script handles: `composer install`, `.env` creation, `key:generate`, `migrate`, `npm install`, and `npm run build`._

### Manual Setup

1. **Install Dependencies**:

    ```bash
    composer install
    npm install
    ```

2. **Environment Configuration**:

    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

3. **Database Migration**:
    ```bash
    php artisan migrate
    ```

---

## 💻 Development

Start all necessary services (Vite, Laravel Server, Queue Listeners, and Logs) with a single command:

```bash
composer dev
```

---

## 📡 API Overview (v1)

| Endpoint                   | Method | Description                      | Auth Required |
| :------------------------- | :----- | :------------------------------- | :-----------: |
| `/products`                | `GET`  | List available products          |      ❌       |
| `/products/{slug}`         | `GET`  | Get product details              |      ❌       |
| `/products`                | `POST` | Create a new product             |      ✅       |
| `/sales`                   | `POST` | Process a new sale               |      ✅       |
| `/reports/sales-report`    | `GET`  | Itemized sales analytics         |      ✅       |
| `/reports/purchase-report` | `GET`  | Supplier spend analysis          |      ✅       |
| `/reports/daily-closing`   | `GET`  | Reconciliation & cash flow stats |      ✅       |
| `/reports/stock-audit`     | `GET`  | Inventory discrepancy log        |      ✅       |

---

## 🏗 Database Seeding

The project comes with a comprehensive suite of seeders to populate the environment with realistic commerce data.

To re-seed the entire database:

```bash
php artisan migrate:fresh --seed
```

**Available Seeders**:

- `BranchSeeder`: Sets up retail locations (e.g., Medan Warehouse, Jakarta Store).
- `SupplierSeeder`: Common vendor profiles.
- `ProductSeeder`: Populates the catalog with diverse category assignments.
- `SaleSeeder`: Generates historical transaction records for testing reports.
- `PurchaseOrderSeeder`: Procurement cycle data.
- `StockLogSeeder`: Complete audit trail for every single item movement.

---

## 🧪 Testing

Run the comprehensive test suite to ensure system stability:

```bash
composer test
```

---

## 📄 License

The CommerceSystem-API is open-sourced software licensed under the [MIT license](LICENSE).
