<p align="center">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset="https://placehold.co/600x120/1a1a2e/e0e0e0?text=Shop+API&font=source-code-pro">
    <img src="https://placehold.co/600x120/e8f5e9/2e7d32?text=Shop+API&font=source-code-pro" alt="Shop API" width="600">
  </picture>
</p>

<p align="center">
  <strong>A headless e-commerce API built with Laravel 13 & Filament v5</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.3-777BB4?style=flat&logo=php" alt="PHP 8.3">
  <img src="https://img.shields.io/badge/Laravel-13-FF2D20?style=flat&logo=laravel" alt="Laravel 13">
  <img src="https://img.shields.io/badge/Filament-v5-FFAA00?style=flat&logo=filament" alt="Filament v5">
  <img src="https://img.shields.io/badge/Sanctum-4-FF2D20?style=flat" alt="Sanctum 4">
  <img src="https://img.shields.io/badge/Pest-4-FF2D20?style=flat&logo=pest" alt="Pest 4">
  <img src="https://img.shields.io/badge/Tailwind-4-06B6D4?style=flat&logo=tailwindcss" alt="Tailwind 4">
  <img src="https://img.shields.io/badge/license-MIT-blue" alt="License">
</p>

---

## Overview

**Shop API** is a RESTful e-commerce backend that powers an online store. It provides a fully-featured API for product catalog management, user authentication, shopping cart operations, order processing, inventory tracking, and more — all managed through a Filament admin panel.

This project was built as a portfolio application to demonstrate modern Laravel development skills — including API design, database architecture, testing, and administration interfaces.

---

## Features

<details>
<summary><strong>🔐 Authentication</strong> — Sanctum token-based auth</summary>

- User registration with auto-generated API tokens
- Login / logout with token revocation
- Protected routes for orders, wishlist, and cart
</details>

<details>
<summary><strong>📦 Product Catalog</strong> — Full CRUD with filtering</summary>

- Create, read, update, soft-delete products
- Search by name/description
- Filter by category slug, featured status
- Sort by latest, limit results
</details>

<details>
<summary><strong>🏷️ Categories</strong> — Product categorization</summary>

- Full CRUD with soft deletes
- Products automatically load with their category
</details>

<details>
<summary><strong>📐 Sizes & Variants</strong> — Per-size pricing & stock</summary>

- Many-to-many relationship via pivot table
- Each size has its own additional price and stock count
- Products can be simple (no sizes) or sized
</details>

<details>
<summary><strong>💰 Discounts</strong> — Scheduled promotions</summary>

- Percentage or fixed-amount discounts
- Date-scheduled activation windows
- Auto-calculated sale price on products
- Many-to-many product association
</details>

<details>
<summary><strong>🛒 Cart Preview</strong> — Validate before ordering</summary>

- Stock availability check for each item
- Unit price calculation (base + size add-on — discount)
- Returns itemized pricing summary
</details>

<details>
<summary><strong>📋 Order Management</strong> — Transactional ordering</summary>

- Atomic order placement wrapped in DB transactions
- Auto-decrements stock on confirmation
- Unique order number generation (`ORD-YYYYMMDD-XXXX`)
- User-scoped order history & detail views
- Automatic stock restoration when orders are cancelled (via Observer)
</details>

<details>
<summary><strong>❤️ Wishlist</strong> — Per-user favorites</summary>

- Add/remove products to/from wishlist
- Unique constraint prevents duplicates
</details>

<details>
<summary><strong>🎯 Banners</strong> — Position-based promotional banners</summary>

- Hero and sidebar positions
- Date-scheduled display windows
- Configurable CTA, colors, and sort order
- Separate desktop and mobile images
</details>

<details>
<summary><strong>📊 Inventory Tracking</strong> — Full audit trail</summary>

- Tracks every stock change (initial, adjustment, order, refund)
- Records before/after quantities with reason
- Links movements to orders and users
- Low-stock and out-of-stock admin widgets
</details>

<details>
<summary><strong>🖥️ Admin Panel</strong> — Filament v5 CRUD interface</summary>

- Full CRUD for: Products, Categories, Orders, Banners, Discounts, Sizes
- Inventory view: read-only list with stock levels
- Dashboard widgets: low-stock alerts, out-of-stock counters
- Soft-delete awareness (filter trashed products)
</details>

---

## Tech Stack

| Layer | Technology |
|---|---|
| **Backend** | PHP 8.3, Laravel 13 |
| **Admin Panel** | Filament v5 |
| **API Authentication** | Laravel Sanctum |
| **Database** | SQLite (development) |
| **Testing** | Pest v4 / PHPUnit 12 |
| **Frontend** | Tailwind CSS v4, Vite |
| **File Storage** | AWS S3 |
| **Development** | Laravel Herd, Laravel Boost |

---

## Architecture Highlights

| Pattern | Implementation |
|---|---|
| **API Versioning** | All endpoints namespaced under `Api\V1` Controllers |
| **API Resources** | `ProductResource`, `CategoryResource`, `OrderResource`, `BannerResource` for consistent JSON |
| **Form Requests** | Dedicated `StoreProductRequest`, `UpdateProductRequest`, `StoreOrderRequest` for validation |
| **Observer Pattern** | `OrderObserver` listens for status changes and auto-restores inventory on cancellation |
| **Service Layer** | `InventoryService` centralizes stock logic (adjust, reserve, restore) |
| **PHP 8 Enums** | `OrderStatus`, `PaymentStatus`, `DiscountType` — backed string enums |
| **Soft Deletes** | Products and Categories support soft deletion |
| **Database Transactions** | Order placement wrapped in atomic transactions |

---

## API Endpoints

### Authentication

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/register` | — | Create account & get token |
| POST | `/api/login` | — | Login & get token |
| POST | `/api/logout` | ✅ | Revoke current token |

### Products

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/v1/products` | — | List products (search, filter, sort) |
| GET | `/api/v1/products/by-slug/{slug}` | — | Get product by slug |
| GET | `/api/v1/products/{product}` | — | Get product by ID |
| POST | `/api/v1/products` | — | Create product |
| PUT | `/api/v1/products/{product}` | — | Update product |
| DELETE | `/api/v1/products/{product}` | — | Soft-delete product |

### Categories

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/v1/categories` | — | List categories |
| GET | `/api/v1/categories/{category}` | — | Get category (includes products) |
| POST | `/api/v1/categories` | — | Create category |
| PUT | `/api/v1/categories/{category}` | — | Update category |
| DELETE | `/api/v1/categories/{category}` | — | Soft-delete category |

### Orders

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/v1/orders` | ✅ | List own orders (paginated) |
| POST | `/api/v1/orders` | ✅ | Place an order |
| GET | `/api/v1/orders/{order}` | ✅ | Get order details |

### Cart

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/v1/cart/preview` | ✅ | Preview cart (stock & pricing) |

### Wishlist

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/v1/wishlist` | ✅ | List wishlist items |
| POST | `/api/v1/wishlist` | ✅ | Add to wishlist |
| DELETE | `/api/v1/wishlist/{product}` | ✅ | Remove from wishlist |

### Banners

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/v1/banners` | — | List active banners (by position) |

---

## Database Schema

```
users          → orders → order_items
                    ↓
categories    → products → product_size → sizes
                    ↓
              discounts → discount_product
                    ↓
              wishlists (user ↔ product)
                    ↓
              stock_movements
              
banners (standalone)
```

**Key tables:** `users`, `categories`, `products`, `sizes`, `product_size`, `orders`, `order_items`, `wishlists`, `stock_movements`, `banners`, `discounts`, `discount_product`

---

## Admin Panel

A full-featured Filament v5 admin panel is available at `/admin`.

<p align="center">
  <img src="https://placehold.co/800x450/e8f5e9/2e7d32?text=Admin+Panel+Screenshot" alt="Admin Panel" width="600">
  <br>
  <em>Screenshot coming soon</em>
</p>

### Resources

| Resource | Features |
|---|---|
| **Products** | Form with tabs, image upload, soft-delete toggle, size sync |
| **Categories** | CRUD with slug auto-generation |
| **Orders** | Status management, payment tracking, item breakdown |
| **Banners** | Image upload, scheduling, position & ordering |
| **Discounts** | Type/value configuration, product association, scheduling |
| **Sizes** | Simple CRUD for variant sizes |
| **Inventory** | Read-only view of product stock levels |

### Dashboard Widgets

- **Low Stock Widget** — Products with stock between 1–10 units
- **Out of Stock Widget** — Products with zero stock

---

## Getting Started

```bash
# Clone the repository
git clone https://github.com/yourusername/shop-api.git
cd shop-api

# Install PHP dependencies
composer install

# Install frontend dependencies (if needed)
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Run migrations and seed data
php artisan migrate --seed

# Start the development server
php artisan serve
```

The API will be available at `http://localhost:8000/api/v1`.

> **Note:** For image uploads, configure AWS S3 credentials in `.env`. For local development, you can use the `public` disk.

---

## Running Tests

```bash
php artisan test --compact
```

### Test Coverage

| Test File | What It Covers |
|---|---|
| `BannerApiTest` | Banner listing, position filtering, date scheduling, empty state |
| `InventoryServiceTest` | Stock adjustments, order reservation, cancel restoration, idempotency |
| `ExampleTest` | Root redirect to admin panel |

---

## Key Takeaways

In building Shop API, I focused on:

- **Clean API Design** — Versioned RESTful endpoints with consistent JSON responses via Eloquent API Resources
- **Data Integrity** — Database transactions for order placement, idempotent stock restoration
- **Inventory Accuracy** — Complete audit trail for every stock movement
- **Test-Driven Mindset** — Functional tests for critical business logic (inventory, banners)
- **Developer Experience** — Filament admin panel for easy data management
- **Modern PHP** — PHP 8 attributes, backed enums, typed properties, constructor promotion

---

## Future Improvements

- [ ] Payment gateway integration (Stripe / PayPal)
- [ ] Order confirmation & shipment email notifications
- [ ] API rate limiting
- [ ] Automated API documentation (Scribe / Scramble)
- [ ] User roles & permissions (admin vs customer)
- [ ] Product reviews & ratings
- [ ] Coupon/promotion code system
- [ ] CI/CD pipeline with GitHub Actions

---

## License

This project is open-sourced under the [MIT license](https://opensource.org/licenses/MIT).

---

<p align="center">
  Built with ❤️ using <a href="https://laravel.com">Laravel</a> & <a href="https://filamentphp.com">Filament</a>
</p>
