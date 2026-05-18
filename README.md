# ⚙️ CommerceSystem-API

### The AI-Powered Enterprise Engine & Core Orchestration Hub

[![Laravel 12](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel)](https://laravel.com)
[![PHP 8.4](https://img.shields.io/badge/PHP-8.4+-777BB4?style=for-the-badge&logo=php)](https://www.php.net/)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16.x-336791?style=for-the-badge&logo=postgresql)](https://www.postgresql.org/)
[![Sanctum](https://img.shields.io/badge/Auth-Sanctum-brightgreen?style=for-the-badge)](https://laravel.com/docs/12.x/sanctum)
[![Real-Time](https://img.shields.io/badge/Real_Time-Reverb-blue?style=for-the-badge&logo=websocket)](https://laravel.com/docs/broadcasting)

**CommerceSystem-API** is the central nervous system and production-ready backbone powering the entire CommerceSystem ecosystem. Rather than serving as a passive database, this engine acts as an **AI Orchestration Hub** built with **Laravel 12** and **PHP 8.4**. It natively bridges enterprise ERP operations with Google DeepMind's models via Vertex AI and Google AI Studio, enabling autonomous agentic workflows across digital and physical storefronts.

---

## 🚀 TechEx Multi-Repo Ecosystem Mapping

Because this is an interconnected enterprise-grade solution, the project is structured modularly across specialized layers:

- ⚙️ **[CommerceSystem-API](https://github.com/eCarlsson-r/CommerceSystem-API)** (This Repository): Central Laravel Backend, Database, and Vertex AI Gateway.

- 🛍️ **[CommerceStore Frontend](https://carlsson-commerce-store.vercel.app)**: Next.js Consumer Web App with interactive 3D Spatial Calculation Canvas and Sales Concierge Sidebar.

- 🖥️ **[CommercePOS Frontend](https://carlsson-commerce-pos.vercel.app)**: In-store clerk Point of Sale & Physical Inventory Management Terminal.

---

## ✨ Intelligent Agentic & ERP Features

### 🧠 Core AI & Agentic Orchestration Layer ( LaravelAiKitService )

Our dedicated service core directly orchestrates multi-modal requests, state evaluations, and autonomous routines using Google's foundational models:

- **Gemini-Powered Assistant Framework**: Handles semantic intent parsing and contextual history matching. It processes incoming text to automatically generate follow-up diagnostics and calculate physical materials.

- **Autonomous Inventory Replenishment Agent**: Uses Gemini Function Calling patterns. When low stock metrics are triggered through POS sales logs, the system moves beyond dialogue to execute secure API requests that programmatically compile procurement drafts.

- **Multi-Strategy ML Recommendation Matrix**: Merges real-time data through 5 analytical channels: Collaborative Filtering (co-purchase correlation), Content-Based Filtering (price/category clustering), Personalized User History, Contextual Style Tags, and Best-Seller fallbacks.

- **Multi-Modal Vision & Inpainting Gateway**: Integrates with imagen-3.0-capability-001 via Vertex AI for mask-free background modification, reading uploaded consumer canvas profiles and projecting matching materials directly onto room walls.

### ️🏪 Customer-Facing Storefront APIs

Optimized for high-conversion e-commerce experiences with lightning-fast response times.

- **Intelligent Product Discovery**: Advanced algorithmic query parsing, item indexing, and paginated outputs.
- **SEO-Optimized Metadata Engine**: Custom slug extraction and high-density crawling profiles.
- **Omnichannel Availability matrix**: True branch-level distribution lookups.

### 🖥️ Clerk POS & Inventory Operations APIs

- **Historical Audit Trails**: Complete chronological lineage logging for every stock delta.
- **Omnichannel Order Lifecycle**: Central routing and reconciliation for online deliveries, in-store cash sales, and returns.
- **Financial Reporting Engines**: Deep-dive daily closings, purchase logging, and cash flow sheets.

### 📡 Real-Time Broadcasting & Synchronization

- **WebSocket Core**: Live browser UI synchronization powered natively by Laravel Reverb.
- **WebPush Notification Dispatch**: Event-driven worker streams pushing structural system shifts, low-stock warnings, and purchase alerts instantly to managers.

---

## 🛠 Technical Architecture Highlights

- **Google Cloud Platform Integration**: Authenticated using Cloud Platform service accounts to connect directly with global API publisher boundaries via exponential backoff filters.
- **Laravel Sanctum Identity Management**: Stateful and token-based authentication schemas managing strict role privileges (Admins, Managers, Cashiers, Customers).
- **Asynchronous Queue Pipeline**: Background worker orchestration isolating demanding tasks like AI text generations, image formatting, and report assemblies out of the HTTP thread loop.
- **Defensive Design Patterns**: Strict OWASP compliance profiles utilizing input type checking, database parameter filtering, and cross-site mitigation patterns.
- **Sub-100ms Responses**: Eager data hydration patterns and multi-tier memory caching structures.

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

---

## 📊 Core API Routing Matrix

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

## 🧪 Testing Coverage

Ensure framework safety configurations remain intact by executing the test block:

```bash
composer test
```

Built with automated feature evaluation parameters preserving high functional code test coverage.

---

## 📄 License

Proprietary software part of the CommerceSystem ecosystem.
