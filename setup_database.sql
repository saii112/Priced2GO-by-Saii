-- Run this entire file in phpMyAdmin > SQL tab
-- after creating a database called priced2go_db

CREATE TABLE IF NOT EXISTS users (
    user_id       INT AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(100) NOT NULL,
    email         VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone         VARCHAR(20),
    address       TEXT,
    role          ENUM('buyer','seller','admin') DEFAULT 'buyer',
    status        ENUM('active','suspended') DEFAULT 'active',
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS listings (
    listing_id   INT AUTO_INCREMENT PRIMARY KEY,
    seller_id    INT NOT NULL,
    title        VARCHAR(200) NOT NULL,
    description  TEXT,
    category     VARCHAR(100),
    listing_type ENUM('fixed','auction') DEFAULT 'fixed',
    price        DECIMAL(10,2),
    start_price  DECIMAL(10,2),
    current_bid  DECIMAL(10,2),
    end_time     DATETIME,
    status       ENUM('active','sold','expired') DEFAULT 'active',
    image_path   VARCHAR(255),
    condition_label VARCHAR(50),
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Stores all images for a listing (gallery, up to 5 per listing). The
-- listings.image_path column keeps the first image for thumbnails / back-compat.
CREATE TABLE IF NOT EXISTS listing_images (
    image_id   INT AUTO_INCREMENT PRIMARY KEY,
    listing_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    sort_order INT DEFAULT 0,
    FOREIGN KEY (listing_id) REFERENCES listings(listing_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS bids (
    bid_id     INT AUTO_INCREMENT PRIMARY KEY,
    listing_id INT NOT NULL,
    bidder_id  INT NOT NULL,
    bid_amount DECIMAL(10,2) NOT NULL,
    bid_time   DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (listing_id) REFERENCES listings(listing_id) ON DELETE CASCADE,
    FOREIGN KEY (bidder_id)  REFERENCES users(user_id)    ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS orders (
    order_id     INT AUTO_INCREMENT PRIMARY KEY,
    buyer_id     INT NOT NULL,
    listing_id   INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status       ENUM('pending','paid','shipped','complete','cancelled') DEFAULT 'pending',
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (buyer_id)   REFERENCES users(user_id)    ON DELETE CASCADE,
    FOREIGN KEY (listing_id) REFERENCES listings(listing_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS payments (
    payment_id      INT AUTO_INCREMENT PRIMARY KEY,
    order_id        INT NOT NULL,
    amount          DECIMAL(10,2) NOT NULL,
    method          VARCHAR(50),
    status          ENUM('pending','confirmed','declined') DEFAULT 'pending',
    transaction_ref VARCHAR(100),
    paid_at         DATETIME,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
);

-- Default admin account (password: admin123)
INSERT INTO users (username, email, password_hash, role, status)
VALUES ('admin', 'admin@priced2go.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active');

-- Sample listings for testing
INSERT INTO users (username, email, password_hash, role) VALUES
('john_doe',  'john@test.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'seller'),
('jane_smith','jane@test.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'buyer');

INSERT INTO listings (seller_id, title, description, category, listing_type, price, status) VALUES
(2, 'Vintage Camera',   'A beautiful vintage film camera in great condition.', 'Electronics',     'fixed',   120.00, 'active'),
(2, 'Acoustic Guitar',  'Yamaha acoustic guitar, barely used.',                'Musical Instruments','fixed', 200.00, 'active'),
(2, 'Mountain Bike',    'Trek mountain bike, 21-speed, good condition.',       'Sports',           'fixed',   350.00, 'active'),
(2, 'Gaming Headset',   'Professional gaming headset with surround sound.',   'Electronics',      'fixed',    75.00, 'active');

INSERT INTO listings (seller_id, title, description, category, listing_type, start_price, current_bid, end_time, status) VALUES
(2, 'Designer Sunglasses', 'Authentic Ray-Ban sunglasses, like new.', 'Accessories', 'auction', 50.00, 80.00, DATE_ADD(NOW(), INTERVAL 3 DAY), 'active'),
(2, 'Vintage Watch',       'Seiko automatic watch, 1970s.',           'Accessories', 'auction', 100.00, 250.00, DATE_ADD(NOW(), INTERVAL 2 DAY), 'active');
