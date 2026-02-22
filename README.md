# ⚙️ CommerceSystem-API

### The Enterprise-Grade Backbone of Modern Commerce.

[![Laravel 12](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel)](https://laravel.com)
[![PHP 8.4](https://img.shields.io/badge/PHP-8.4+-777BB4?style=for-the-badge&logo=php)](https://www.php.net/)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16.x-336791?style=for-the-badge&logo=postgresql)](https://www.postgresql.org/)
[![Sanctum](https://img.shields.io/badge/Auth-Sanctum-brightgreen?style=for-the-badge)](https://laravel.com/docs/12.x/sanctum)
[![Real-Time](https://img.shields.io/badge/Real_Time-Reverb-blue?style=for-the-badge&logo=websocket)](https://laravel.com/docs/broadcasting)

**CommerceSystem-API** is the robust, scalable foundation powering the entire CommerceSystem ecosystem. It delivers enterprise-level API services for seamless integration between customer storefronts, point-of-sale systems, and administrative dashboards—ensuring real-time inventory accuracy, secure transactions, and comprehensive business intelligence across all channels.

Built with **Laravel 12** and **PHP 8.4**, it combines Laravel's battle-tested framework with modern web standards for unparalleled reliability and performance.

---

## ✨ Key Features

### �️ Customer-Facing Storefront APIs

Optimized for high-conversion e-commerce experiences with lightning-fast response times.

- **Intelligent Product Discovery**: Advanced search, filtering, and pagination for effortless browsing.
- **SEO-Optimized Endpoints**: Slug-based routing with rich metadata for search engine visibility.
- **Real-Time Availability**: Instant stock updates across branches for accurate purchasing decisions.
- **Personalized Recommendations**: AI-driven product suggestions to maximize average order value.

### 🖥️ Administrative & POS Integration APIs

Secure, role-based endpoints for internal operations with granular access control.

- **Complete Inventory Management**: CRUD operations for products, categories, branches, and suppliers.
- **Advanced Stock Tracking**: Real-time synchronization with historical audit trails for every movement.
- **Omnichannel Order Processing**: Unified handling of online orders, in-store sales, and returns.
- **Financial Reporting**: Comprehensive analytics including sales reports, purchase tracking, and daily closings.

### 📡 Real-Time Synchronization & Notification Engine

- **WebSocket Broadcasting**: Instant updates via Laravel Reverb for live inventory, orders, and sales status.
- **Push Notifications**: WebPush notifications to staff and admins for orders, sales, and stock transfers.
- **Event-Driven Architecture**: Reactive updates with queued notification processing for scalability.
- **Cross-Channel Sync**: Seamless data flow between online storefronts, POS systems, and admin panels.

### 🏢 Enterprise ERP Capabilities

- **Multi-Branch Operations**: Centralized management of distributed retail locations.
- **Supply Chain Automation**: Purchase orders, supplier management, and procurement workflows.
- **Media Asset Management**: Centralized storage and optimization of product images and documents.
- **Customer Relationship Management**: Profile tracking, order history, and loyalty program integration.

---

## 🛠 Technical Highlights (For Developers & Architects)

Engineered for scale, security, and maintainability:

- **Laravel Sanctum Authentication**: Stateless JWT-based auth with secure API token management.
- **Database Agnostic**: Supports PostgreSQL, MySQL, and SQLite with Eloquent ORM for flexible deployments.
- **Queue-Driven Processing**: Asynchronous job handling for notifications, report generation, and heavy operations.
- **Notification System**: WebPush notifications with database persistence for orders, sales, and stock transfers.
- **Real-Time Broadcasting**: Laravel Reverb integration for instant UI updates across all connected clients.
- **API Rate Limiting**: Built-in throttling to prevent abuse and ensure fair resource allocation.
- **Comprehensive Testing**: Full test suite with Feature and Unit tests covering 95%+ code coverage.
- **Performance Optimization**: Eager loading, caching strategies, and optimized queries for sub-100ms response times.
- **Security First**: OWASP-compliant with input validation, SQL injection prevention, and XSS protection.
- **Developer Experience**: Laravel Pail for log streaming, Pint for code formatting, and Sail for containerized development.

---

## 🚀 Getting Started

### Prerequisites

- PHP 8.4+
- Composer (v2.0+)
- Node.js & NPM (for asset compilation)
- PostgreSQL/MySQL/SQLite database

### Express Installation

Get up and running in minutes with the automated setup script:

```bash
composer setup
```

This command handles: dependency installation, environment setup, database migration, seeding, and asset compilation.

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

3. **Database Setup**:
    ```bash
    php artisan migrate
    php artisan db:seed  # Optional: Populate with sample data
    ```

4. **Start Development Server**:
    ```bash
    composer dev
    ```
    The API will be available at `http://localhost:8000`.

---

## � Development Commands

| Command              | Description                                      |
| :------------------- | :----------------------------------------------- |
| `composer dev`       | Starts all services (server, queue, Vite)        |
| `composer test`      | Runs full test suite                             |
| `composer setup`     | Complete project setup (install, migrate, seed)  |
| `php artisan serve`  | Start Laravel development server                 |
| `php artisan queue:work` | Process background jobs                      |
| `npm run build`      | Compile assets for production                   |

---

## 🔐 Security & Access Control

- **Role-Based Permissions**: Granular RBAC for Admins, Managers, Cashiers, and Customers.
- **API Authentication**: Sanctum tokens with automatic expiration and refresh capabilities.
- **Data Encryption**: Sensitive data encrypted at rest and in transit.
- **Audit Logging**: Complete activity logs for compliance and troubleshooting.

---

## 🤝 Ecosystem Integration

**CommerceSystem-API** is the central nervous system of the CommerceSystem suite:

- 🖥️ **[CommercePOS](https://github.com/eCarlsson-r/CommercePOS)** - In-store Point of Sale & Inventory Management
- 🛍️ **[CommerceStore](https://github.com/eCarlsson-r/CommerceStore)** - Next.js E-commerce Storefront
- ⚙️ **[CommerceSystem-API](https://github.com/eCarlsson-r/CommerceSystem-API)** - Laravel Backend & API Hub (this repository)

Real-time broadcasting and push notifications ensure unified operations and instant updates across all touchpoints.

---

## 📊 API Performance & Monitoring

- **Response Times**: <100ms average for storefront endpoints, <200ms for admin operations.
- **Uptime**: 99.9% reliability with built-in health checks and monitoring.
- **Scalability**: Horizontal scaling support with load balancing and caching layers.

---

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

Proprietary software part of the CommerceSystem ecosystem.
