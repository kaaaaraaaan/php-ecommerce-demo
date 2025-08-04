-- Ecommerce Database Schema
CREATE DATABASE IF NOT EXISTS ecommerce;
USE ecommerce;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products table
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    image_url VARCHAR(255),
    stock_quantity INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Cart table
CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Orders table
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Order items table
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, email, password, is_admin) VALUES 
('admin', 'admin@example.com', 'admin123', TRUE);

-- Contacts table
CREATE TABLE contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('new', 'read', 'replied', 'archived') DEFAULT 'new'
);

-- Insert sample products
INSERT INTO products (name, description, price, image_url, stock_quantity) VALUES 
('MacBook Pro M3', 'Apple MacBook Pro with M3 chip, 16GB RAM, 512GB SSD, 14-inch Liquid Retina XDR display, Space Gray', 1999.99, 'uploads/products/MacBook Pro M3.png', 15),
('Samsung Galaxy S24 Ultra', 'Samsung Galaxy S24 Ultra with 6.8" Dynamic AMOLED display, 200MP camera, Snapdragon 8 Gen 3, 12GB RAM, 256GB storage, Titanium Black', 1299.99, 'uploads/products/Samsung Galaxy S24 Ultra.png', 30),
('Sony WH-1000XM5', 'Sony WH-1000XM5 Wireless Noise Cancelling Headphones with Auto Noise Cancelling Optimizer, Crystal Clear Hands-Free Calling, and Alexa Voice Control, Black', 349.99, 'uploads/products/Sony WH-1000XM5.png', 45),
('iPad Pro M2', 'Apple iPad Pro 12.9-inch with M2 chip, Liquid Retina XDR display, 256GB, Wi-Fi 6E, Silver, 2022 model', 1099.99, 'uploads/products/iPad Pro M2.png', 20),
('ASUS ROG Strix G17', 'ASUS ROG Strix G17 Gaming Laptop with AMD Ryzen 9 7945HX, NVIDIA GeForce RTX 4080, 32GB DDR5, 1TB PCIe 4.0 SSD, 17.3" QHD 240Hz Display', 2499.99, 'uploads/products/ASUS ROG Strix G17.png', 8),
('Bose QuietComfort Ultra', 'Bose QuietComfort Ultra Headphones with Spatial Audio, World-class noise cancellation, Bluetooth, Up to 24 hours battery life, Black', 429.99, 'uploads/products/Bose QuietComfort Ultra.png', 25),
('DJI Mini 3 Pro', 'DJI Mini 3 Pro Drone with 4K HDR Video, 48MP Photo, 34-min Flight Time, Tri-Directional Obstacle Sensing, Ideal for Aerial Photography and Social Media', 759.99, 'uploads/products/DJI Mini 3 Pro.png', 12);
