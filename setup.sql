-- =============================================
-- SELLER APP v2 - Database Setup
-- phpMyAdmin मध्ये sai7755_blog database निवडा
-- मग हे SQL run करा
-- =============================================

USE sai7755_blog;

-- 1. Products Table
CREATE TABLE IF NOT EXISTS products (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(255) NOT NULL,
    sku         VARCHAR(100) UNIQUE,
    price       DECIMAL(10,2) NOT NULL,
    mrp         DECIMAL(10,2),
    stock       INT NOT NULL DEFAULT 0,
    category    VARCHAR(100) DEFAULT 'Other',
    description TEXT,
    image_url   VARCHAR(500),
    on_amazon      TINYINT(1) DEFAULT 0,
    on_flipkart    TINYINT(1) DEFAULT 0,
    amazon_status  VARCHAR(50) DEFAULT 'not_listed',
    flipkart_status VARCHAR(50) DEFAULT 'not_listed',
    amazon_listing_id  VARCHAR(100),
    flipkart_listing_id VARCHAR(100),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. API Settings Table (Sandbox credentials)
CREATE TABLE IF NOT EXISTS api_settings (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    platform    VARCHAR(50) NOT NULL,
    app_id      VARCHAR(300),
    app_secret  VARCHAR(500),
    access_token TEXT,
    token_expiry DATETIME,
    sandbox_mode TINYINT(1) DEFAULT 1,
    is_active   TINYINT(1) DEFAULT 0,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 3. API Logs Table (debugging साठी)
CREATE TABLE IF NOT EXISTS api_logs (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    platform   VARCHAR(50),
    action     VARCHAR(100),
    request    TEXT,
    response   TEXT,
    status     VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Default rows insert करा
INSERT IGNORE INTO api_settings (platform, sandbox_mode) VALUES ('flipkart', 1);


-- Demo products
INSERT IGNORE INTO products (name, sku, price, mrp, stock, category) VALUES
('Cotton Kurti White',     'KRT-001', 549.00,  799.00,  45,  'Clothing'),
('Bluetooth Speaker Mini', 'SPK-002', 1299.00, 1999.00, 8,   'Electronics'),
('Steel Water Bottle 1L',  'BTL-003', 399.00,  599.00,  120, 'Home'),
('Yoga Mat Non-Slip',      'YGA-004', 699.00,  999.00,  15,  'Sports');

SELECT 'Setup complete! ✅' AS message;
