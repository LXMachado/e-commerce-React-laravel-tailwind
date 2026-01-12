# Weekender Solar E-commerce Database Schema Design

## Overview
This document outlines the complete database schema for the Weekender solar e-commerce platform, designed to support solar product sales, inventory management, order processing, and content management.

## Core Design Principles

### 1. **Inventory Management**
- Track inventory at the variant level, not product level
- Support for product variations (wattage, color, size)
- Real-time stock quantity updates
- Price snapshots for historical accuracy

### 2. **Scalability**
- Normalized database structure
- Strategic indexes for e-commerce performance
- Support for guest and authenticated users
- Extensible attribute system for solar product specifications

### 3. **Business Logic**
- Bundle pricing can differ from individual item totals
- Guest carts merge with user accounts on login
- Order numbers are unique and auto-generated
- Soft deletes for content, but not for financial records

## Entity Relationship Diagram

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│ Categories  │─────│ Products    │─────│  Variants   │
│             │     │             │     │             │
│ - Hierarchical    │ - BelongsToMany   │ - BelongsTo │
│ - Self-referencing│ - Categories      │ - Product   │
└─────────────┘     └─────────────┘     └─────────────┘
        │                    │                    │
        └────────────────────┼────────────────────┘
                             │
                    ┌────────▼────────┐
                    │   Attributes    │
                    │                 │
                    │ - Product specs │
                    │ - Solar-specific│
                    └─────────────────┘

┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   Bundles   │─────│ Cart_Items  │─────│   Orders    │
│             │     │             │     │             │
│ - Product   │     │ - Snapshot  │     │ - Complete  │
│ - Bundles   │     │ - Pricing   │     │ - Order     │
└─────────────┘     └─────────────┘     └─────────────┘
        │                    │                    │
        └────────────────────┼────────────────────┘
                             │
                    ┌────────▼────────┐
                    │   Payments      │
                    │                 │
                    │ - Stripe        │
                    │ - Integration   │
                    └─────────────────┘
```

## Detailed Table Specifications

### 1. Categories Table
**Purpose**: Hierarchical categorization for solar products

```sql
CREATE TABLE categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    parent_id BIGINT UNSIGNED NULL,
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT true,
    seo_title VARCHAR(255),
    seo_description TEXT,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE CASCADE,
    INDEX idx_categories_parent_id (parent_id),
    INDEX idx_categories_slug (slug),
    INDEX idx_categories_active (is_active)
);
```

**Business Rules**:
- Self-referencing hierarchy for subcategories
- Slugs must be unique for SEO URLs
- Soft delete support for category management

### 2. Products Table
**Purpose**: Core product information and metadata

```sql
CREATE TABLE products (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    short_description VARCHAR(500),
    sku VARCHAR(100) UNIQUE NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    compare_at_price DECIMAL(10,2) NULL,
    cost_price DECIMAL(10,2) NULL,
    track_inventory BOOLEAN DEFAULT true,
    weight_g INT NULL,
    dimensions VARCHAR(255) NULL COMMENT 'L x W x H in cm',
    is_active BOOLEAN DEFAULT true,
    seo_title VARCHAR(255),
    seo_description TEXT,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    INDEX idx_products_slug (slug),
    INDEX idx_products_sku (sku),
    INDEX idx_products_active (is_active),
    INDEX idx_products_price (price)
);
```

**Business Rules**:
- Every product must have at least one variant
- SKU is unique across all products
- Price and cost tracking for profit margins

### 3. Product Variants Table
**Purpose**: Individual product variations with inventory

```sql
CREATE TABLE product_variants (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    sku VARCHAR(100) UNIQUE NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    compare_at_price DECIMAL(10,2) NULL,
    cost_price DECIMAL(10,2) NULL,
    stock_quantity INT NOT NULL DEFAULT 0,
    weight_g INT NULL,
    barcode VARCHAR(255) NULL,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_variants_product_id (product_id),
    INDEX idx_variants_sku (sku),
    INDEX idx_variants_stock (stock_quantity),
    INDEX idx_variants_active (is_active),
    CHECK (stock_quantity >= 0)
);
```

**Business Rules**:
- Stock quantity cannot go negative
- Each variant belongs to exactly one product
- Variant SKUs must be unique globally

### 4. Attributes Table
**Purpose**: Product specification types (solar-specific)

```sql
CREATE TABLE attributes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type ENUM('text', 'number', 'select', 'multiselect') NOT NULL,
    is_required BOOLEAN DEFAULT false,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    INDEX idx_attributes_type (type),
    INDEX idx_attributes_sort (sort_order)
);
```

**Solar-Specific Attributes**:
- Power Input (W)
- Power Output (W)
- Lumen Output
- Battery Capacity (Wh)
- Battery Type
- Certifications (CE, UL, etc.)
- IP Rating
- Operating Temperature Range
- Dimensions
- Weight

### 5. Attribute Values Table
**Purpose**: Possible values for each attribute

```sql
CREATE TABLE attribute_values (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    attribute_id BIGINT UNSIGNED NOT NULL,
    value VARCHAR(255) NOT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (attribute_id) REFERENCES attributes(id) ON DELETE CASCADE,
    INDEX idx_attribute_values_attribute_id (attribute_id),
    INDEX idx_attribute_values_sort (sort_order),
    UNIQUE KEY unique_attribute_value (attribute_id, value)
);
```

### 6. Product Attribute Values (Pivot)
**Purpose**: Link products to their attribute values

```sql
CREATE TABLE product_attribute_values (
    product_id BIGINT UNSIGNED NOT NULL,
    attribute_value_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    PRIMARY KEY (product_id, attribute_value_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (attribute_value_id) REFERENCES attribute_values(id) ON DELETE CASCADE
);
```

### 7. Bundles Table
**Purpose**: Bundled solar solutions and packages

```sql
CREATE TABLE bundles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    compare_at_price DECIMAL(10,2) NULL,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    INDEX idx_bundles_slug (slug),
    INDEX idx_bundles_active (is_active)
);
```

### 8. Bundle Items Table
**Purpose**: Items included in each bundle

```sql
CREATE TABLE bundle_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bundle_id BIGINT UNSIGNED NOT NULL,
    product_variant_id BIGINT UNSIGNED NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (bundle_id) REFERENCES bundles(id) ON DELETE CASCADE,
    FOREIGN KEY (product_variant_id) REFERENCES product_variants(id) ON DELETE CASCADE,
    INDEX idx_bundle_items_bundle_id (bundle_id),
    INDEX idx_bundle_items_variant_id (product_variant_id),
    INDEX idx_bundle_items_sort (sort_order)
);
```

### 9. Carts Table
**Purpose**: Shopping cart support for guests and users

```sql
CREATE TABLE carts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    session_id VARCHAR(255) NULL,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_carts_user_id (user_id),
    INDEX idx_carts_session_id (session_id),
    INDEX idx_carts_active (is_active),
    CHECK (user_id IS NOT NULL OR session_id IS NOT NULL)
);
```

**Business Rules**:
- Either user_id OR session_id must be present (not both)
- Guest carts identified by session_id
- User carts linked by user_id

### 10. Cart Items Table
**Purpose**: Items in shopping cart with price snapshots

```sql
CREATE TABLE cart_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cart_id BIGINT UNSIGNED NOT NULL,
    product_variant_id BIGINT UNSIGNED NOT NULL,
    quantity INT NOT NULL,
    price_at_time DECIMAL(10,2) NOT NULL COMMENT 'Snapshot of price when added',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
    FOREIGN KEY (product_variant_id) REFERENCES product_variants(id) ON DELETE CASCADE,
    INDEX idx_cart_items_cart_id (cart_id),
    INDEX idx_cart_items_variant_id (product_variant_id),
    CHECK (quantity > 0)
);
```

### 11. Orders Table
**Purpose**: Complete order management with status tracking

```sql
CREATE TABLE orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    status ENUM('pending', 'paid', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    subtotal DECIMAL(10,2) NOT NULL,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    shipping_amount DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    shipping_status ENUM('pending', 'processing', 'shipped', 'delivered') DEFAULT 'pending',
    notes TEXT,
    billing_address_id BIGINT UNSIGNED NULL,
    shipping_address_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (billing_address_id) REFERENCES addresses(id),
    FOREIGN KEY (shipping_address_id) REFERENCES addresses(id),
    INDEX idx_orders_user_id (user_id),
    INDEX idx_orders_order_number (order_number),
    INDEX idx_orders_status (status),
    INDEX idx_orders_created_at (created_at)
);
```

### 12. Order Items Table
**Purpose**: Individual items within orders

```sql
CREATE TABLE order_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    product_variant_id BIGINT UNSIGNED NOT NULL,
    quantity INT NOT NULL,
    price_at_time DECIMAL(10,2) NOT NULL,
    line_total DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_variant_id) REFERENCES product_variants(id) ON DELETE RESTRICT,
    INDEX idx_order_items_order_id (order_id),
    INDEX idx_order_items_variant_id (product_variant_id)
);
```

### 13. Payments Table
**Purpose**: Stripe payment integration and tracking

```sql
CREATE TABLE payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    payment_intent_id VARCHAR(255) UNIQUE NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    status ENUM('pending', 'succeeded', 'failed', 'cancelled') NOT NULL,
    payment_method VARCHAR(50),
    metadata JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE RESTRICT,
    INDEX idx_payments_order_id (order_id),
    INDEX idx_payments_intent_id (payment_intent_id),
    INDEX idx_payments_status (status)
);
```

### 14. Shipments Table
**Purpose**: Shipping and tracking information

```sql
CREATE TABLE shipments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    tracking_number VARCHAR(255),
    carrier VARCHAR(100),
    status ENUM('preparing', 'shipped', 'in_transit', 'delivered', 'failed') DEFAULT 'preparing',
    shipped_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    address_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE RESTRICT,
    FOREIGN KEY (address_id) REFERENCES addresses(id),
    INDEX idx_shipments_order_id (order_id),
    INDEX idx_shipments_tracking (tracking_number),
    INDEX idx_shipments_status (status)
);
```

### 15. Addresses Table
**Purpose**: Billing and shipping addresses

```sql
CREATE TABLE addresses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    type ENUM('billing', 'shipping') NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    company VARCHAR(255),
    address_line_1 VARCHAR(255) NOT NULL,
    address_line_2 VARCHAR(255),
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20) NOT NULL,
    country VARCHAR(2) NOT NULL DEFAULT 'US',
    phone VARCHAR(20),
    is_default BOOLEAN DEFAULT false,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_addresses_user_id (user_id),
    INDEX idx_addresses_type (type),
    INDEX idx_addresses_default (is_default)
);
```

### 16. Content Pages Table
**Purpose**: CMS for solar industry content

```sql
CREATE TABLE content_pages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    content TEXT NOT NULL,
    excerpt VARCHAR(500),
    is_published BOOLEAN DEFAULT false,
    seo_title VARCHAR(255),
    seo_description TEXT,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    INDEX idx_content_pages_slug (slug),
    INDEX idx_content_pages_published (is_published),
    INDEX idx_content_pages_sort (sort_order)
);
```

## Migration Files Structure

### Categories Migration
```php
// 2025_10_18_120000_create_categories_table.php
Schema::create('categories', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('slug')->unique();
    $table->text('description')->nullable();
    $table->foreignId('parent_id')->nullable()->constrained('categories')->onDelete('cascade');
    $table->integer('sort_order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->string('seo_title')->nullable();
    $table->text('seo_description')->nullable();
    $table->timestamps();

    $table->index(['parent_id']);
    $table->index(['slug']);
    $table->index(['is_active']);
});
```

### Products Migration
```php
// 2025_10_18_121000_create_products_table.php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('slug')->unique();
    $table->text('description')->nullable();
    $table->string('short_description', 500)->nullable();
    $table->string('sku', 100)->unique();
    $table->decimal('price', 10, 2);
    $table->decimal('compare_at_price', 10, 2)->nullable();
    $table->decimal('cost_price', 10, 2)->nullable();
    $table->boolean('track_inventory')->default(true);
    $table->integer('weight_g')->nullable();
    $table->string('dimensions', 255)->nullable();
    $table->boolean('is_active')->default(true);
    $table->string('seo_title')->nullable();
    $table->text('seo_description')->nullable();
    $table->timestamps();

    $table->index(['slug']);
    $table->index(['sku']);
    $table->index(['is_active']);
    $table->index(['price']);
});
```

### Product Variants Migration
```php
// 2025_10_18_122000_create_product_variants_table.php
Schema::create('product_variants', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_id')->constrained()->onDelete('cascade');
    $table->string('sku', 100)->unique();
    $table->decimal('price', 10, 2);
    $table->decimal('compare_at_price', 10, 2)->nullable();
    $table->decimal('cost_price', 10, 2)->nullable();
    $table->integer('stock_quantity')->default(0);
    $table->integer('weight_g')->nullable();
    $table->string('barcode', 255)->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();

    $table->index(['product_id']);
    $table->index(['sku']);
    $table->index(['stock_quantity']);
    $table->index(['is_active']);

    $table->check('stock_quantity >= 0');
});
```

### Attributes Migration
```php
// 2025_10_18_123000_create_attributes_table.php
Schema::create('attributes', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->enum('type', ['text', 'number', 'select', 'multiselect']);
    $table->boolean('is_required')->default(false);
    $table->integer('sort_order')->default(0);
    $table->timestamps();

    $table->index(['type']);
    $table->index(['sort_order']);
});
```

### Attribute Values Migration
```php
// 2025_10_18_124000_create_attribute_values_table.php
Schema::create('attribute_values', function (Blueprint $table) {
    $table->id();
    $table->foreignId('attribute_id')->constrained()->onDelete('cascade');
    $table->string('value');
    $table->integer('sort_order')->default(0);
    $table->timestamps();

    $table->index(['attribute_id']);
    $table->index(['sort_order']);

    $table->unique(['attribute_id', 'value']);
});
```

### Product Attribute Values Migration
```php
// 2025_10_18_125000_create_product_attribute_values_table.php
Schema::create('product_attribute_values', function (Blueprint $table) {
    $table->foreignId('product_id')->constrained()->onDelete('cascade');
    $table->foreignId('attribute_value_id')->constrained()->onDelete('cascade');
    $table->timestamps();

    $table->primary(['product_id', 'attribute_value_id']);
});
```

### Bundles Migration
```php
// 2025_10_18_126000_create_bundles_table.php
Schema::create('bundles', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('slug')->unique();
    $table->text('description')->nullable();
    $table->decimal('price', 10, 2);
    $table->decimal('compare_at_price', 10, 2)->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();

    $table->index(['slug']);
    $table->index(['is_active']);
});
```

### Bundle Items Migration
```php
// 2025_10_18_127000_create_bundle_items_table.php
Schema::create('bundle_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('bundle_id')->constrained()->onDelete('cascade');
    $table->foreignId('product_variant_id')->constrained()->onDelete('cascade');
    $table->integer('quantity')->default(1);
    $table->integer('sort_order')->default(0);
    $table->timestamps();

    $table->index(['bundle_id']);
    $table->index(['product_variant_id']);
    $table->index(['sort_order']);
});
```

### Carts Migration
```php
// 2025_10_18_128000_create_carts_table.php
Schema::create('carts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
    $table->string('session_id', 255)->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();

    $table->index(['user_id']);
    $table->index(['session_id']);
    $table->index(['is_active']);
});
```

### Cart Items Migration
```php
// 2025_10_18_129000_create_cart_items_table.php
Schema::create('cart_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('cart_id')->constrained()->onDelete('cascade');
    $table->foreignId('product_variant_id')->constrained()->onDelete('cascade');
    $table->integer('quantity');
    $table->decimal('price_at_time', 10, 2);
    $table->timestamps();

    $table->index(['cart_id']);
    $table->index(['product_variant_id']);
});
```

### Orders Migration
```php
// 2025_10_18_130000_create_orders_table.php
Schema::create('orders', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('restrict');
    $table->string('order_number', 50)->unique();
    $table->enum('status', ['pending', 'paid', 'processing', 'shipped', 'delivered', 'cancelled'])->default('pending');
    $table->decimal('subtotal', 10, 2);
    $table->decimal('tax_amount', 10, 2)->default(0);
    $table->decimal('shipping_amount', 10, 2)->default(0);
    $table->decimal('total_amount', 10, 2);
    $table->string('currency', 3)->default('USD');
    $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
    $table->enum('shipping_status', ['pending', 'processing', 'shipped', 'delivered'])->default('pending');
    $table->text('notes')->nullable();
    $table->foreignId('billing_address_id')->nullable()->constrained('addresses');
    $table->foreignId('shipping_address_id')->nullable()->constrained('addresses');
    $table->timestamps();

    $table->index(['user_id']);
    $table->index(['order_number']);
    $table->index(['status']);
    $table->index(['created_at']);
});
```

### Order Items Migration
```php
// 2025_10_18_131000_create_order_items_table.php
Schema::create('order_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('order_id')->constrained()->onDelete('cascade');
    $table->foreignId('product_variant_id')->constrained()->onDelete('restrict');
    $table->integer('quantity');
    $table->decimal('price_at_time', 10, 2);
    $table->decimal('line_total', 10, 2);
    $table->timestamps();

    $table->index(['order_id']);
    $table->index(['product_variant_id']);
});
```

### Payments Migration
```php
// 2025_10_18_132000_create_payments_table.php
Schema::create('payments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('order_id')->constrained()->onDelete('restrict');
    $table->string('payment_intent_id', 255)->unique();
    $table->decimal('amount', 10, 2);
    $table->string('currency', 3)->default('USD');
    $table->enum('status', ['pending', 'succeeded', 'failed', 'cancelled']);
    $table->string('payment_method', 50)->nullable();
    $table->json('metadata')->nullable();
    $table->timestamps();

    $table->index(['order_id']);
    $table->index(['payment_intent_id']);
    $table->index(['status']);
});
```

### Shipments Migration
```php
// 2025_10_18_133000_create_shipments_table.php
Schema::create('shipments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('order_id')->constrained()->onDelete('restrict');
    $table->string('tracking_number', 255)->nullable();
    $table->string('carrier', 100)->nullable();
    $table->enum('status', ['preparing', 'shipped', 'in_transit', 'delivered', 'failed'])->default('preparing');
    $table->timestamp('shipped_at')->nullable();
    $table->timestamp('delivered_at')->nullable();
    $table->foreignId('address_id')->constrained();
    $table->timestamps();

    $table->index(['order_id']);
    $table->index(['tracking_number']);
    $table->index(['status']);
});
```

### Addresses Migration
```php
// 2025_10_18_134000_create_addresses_table.php
Schema::create('addresses', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->enum('type', ['billing', 'shipping']);
    $table->string('first_name', 100);
    $table->string('last_name', 100);
    $table->string('company', 255)->nullable();
    $table->string('address_line_1', 255);
    $table->string('address_line_2', 255)->nullable();
    $table->string('city', 100);
    $table->string('state', 100);
    $table->string('postal_code', 20);
    $table->string('country', 2)->default('US');
    $table->string('phone', 20)->nullable();
    $table->boolean('is_default')->default(false);
    $table->timestamps();

    $table->index(['user_id']);
    $table->index(['type']);
    $table->index(['is_default']);
});
```

### Content Pages Migration
```php
// 2025_10_18_135000_create_content_pages_table.php
Schema::create('content_pages', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->string('slug')->unique();
    $table->text('content');
    $table->string('excerpt', 500)->nullable();
    $table->boolean('is_published')->default(false);
    $table->string('seo_title')->nullable();
    $table->text('seo_description')->nullable();
    $table->integer('sort_order')->default(0);
    $table->timestamps();

    $table->index(['slug']);
    $table->index(['is_published']);
    $table->index(['sort_order']);
});
```

## Eloquent Model Relationships

### Category Model
```php
class Category extends Model
{
    use HasFactory;

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_categories');
    }
}
```

### Product Model
```php
class Product extends Model
{
    use HasFactory;

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'product_categories');
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function attributeValues()
    {
        return $this->belongsToMany(AttributeValue::class, 'product_attribute_values');
    }

    public function attributes()
    {
        return $this->belongsToMany(Attribute::class, 'product_attribute_values')
                    ->withPivot('attribute_value_id');
    }
}
```

### ProductVariant Model
```php
class ProductVariant extends Model
{
    use HasFactory;

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function bundleItems()
    {
        return $this->hasMany(BundleItem::class);
    }
}
```

### Cart Model
```php
class Cart extends Model
{
    use HasFactory;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    public function getTotalAttribute()
    {
        return $this->items->sum(function ($item) {
            return $item->quantity * $item->price_at_time;
        });
    }
}
```

### Order Model
```php
class Order extends Model
{
    use HasFactory;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function shipments()
    {
        return $this->hasMany(Shipment::class);
    }

    public function billingAddress()
    {
        return $this->belongsTo(Address::class, 'billing_address_id');
    }

    public function shippingAddress()
    {
        return $this->belongsTo(Address::class, 'shipping_address_id');
    }
}
```

## Database Seeders

### Solar Product Categories
- Solar Panels
- Solar Batteries
- Inverters
- Mounting Systems
- Accessories
- Bundled Systems

### Sample Solar Products
1. **Solar Panel Variants**:
   - 100W Monocrystalline
   - 200W Monocrystalline
   - 300W Monocrystalline
   - 100W Polycrystalline
   - 200W Polycrystalline

2. **Battery Variants**:
   - 50Ah Lithium-ion
   - 100Ah Lithium-ion
   - 200Ah Lithium-ion
   - 100Ah Lead-acid
   - 200Ah Lead-acid

3. **Inverter Variants**:
   - 1000W Pure Sine Wave
   - 2000W Pure Sine Wave
   - 3000W Pure Sine Wave

### Solar-Specific Attributes
- Power Input (W)
- Power Output (W)
- Lumen Output (lm)
- Battery Capacity (Wh)
- Battery Type (Lithium-ion, Lead-acid)
- Certifications (CE, UL, RoHS)
- IP Rating (IP65, IP67)
- Operating Temperature Range
- Dimensions (mm)
- Weight (kg)

## Performance Considerations

### Critical Indexes
1. **Product Lookups**: `products(slug)`, `products(sku)`
2. **Variant Queries**: `product_variants(product_id)`, `product_variants(sku)`
3. **Cart Operations**: `cart_items(cart_id)`, `carts(user_id)`, `carts(session_id)`
4. **Order Management**: `orders(user_id)`, `orders(order_number)`, `order_items(order_id)`
5. **Inventory Checks**: `product_variants(stock_quantity)`

### Query Optimization
- Compound indexes for common filter combinations
- Covering indexes for frequently accessed columns
- Pagination for large product catalogs
- Database-level constraints for data integrity

## Migration Execution Order

1. `2025_10_18_120000_create_categories_table.php`
2. `2025_10_18_121000_create_products_table.php`
3. `2025_10_18_122000_create_product_variants_table.php`
4. `2025_10_18_123000_create_attributes_table.php`
5. `2025_10_18_124000_create_attribute_values_table.php`
6. `2025_10_18_125000_create_product_attribute_values_table.php`
7. `2025_10_18_126000_create_bundles_table.php`
8. `2025_10_18_127000_create_bundle_items_table.php`
9. `2025_10_18_128000_create_carts_table.php`
10. `2025_10_18_129000_create_cart_items_table.php`
11. `2025_10_18_130000_create_orders_table.php`
12. `2025_10_18_131000_create_order_items_table.php`
13. `2025_10_18_132000_create_payments_table.php`
14. `2025_10_18_133000_create_shipments_table.php`
15. `2025_10_18_134000_create_addresses_table.php`
16. `2025_10_18_135000_create_content_pages_table.php`

## Summary

This database schema provides a solid foundation for a solar e-commerce platform with:

- **Scalable Product Catalog**: Hierarchical categories with variant-level inventory
- **Flexible Attribute System**: Solar-specific product specifications
- **Bundle Support**: Complex product bundling capabilities
- **Complete Order Management**: From cart to delivery
- **Payment Integration**: Stripe-ready payment processing
- **Content Management**: CMS for solar industry content
- **Performance Optimized**: Strategic indexes and constraints

The design supports both guest and authenticated user experiences while maintaining data integrity and providing excellent performance for e-commerce operations.