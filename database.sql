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

-- Categories table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
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
    category_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
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

-- Insert sample categories
INSERT INTO categories (name, description) VALUES 
('Laptops', 'High-performance laptops and notebooks for work and gaming'),
('Smartphones', 'Latest smartphones with cutting-edge technology'),
('Audio', 'Premium headphones, earbuds, and audio equipment'),
('Tablets', 'Tablets and iPad devices for productivity and entertainment'),
('Gaming', 'Gaming laptops, accessories, and equipment'),
('Drones', 'Professional and recreational drones with advanced features');

-- Insert sample products
INSERT INTO products (name, description, price, image_url, stock_quantity, category_id) VALUES 
-- Laptops Category (1)
('MacBook Pro M3', 'Apple MacBook Pro with M3 chip, 16GB RAM, 512GB SSD, 14-inch Liquid Retina XDR display, Space Gray', 1999.99, 'uploads/products/MacBook Pro M3.png', 15, 1),
('Dell XPS 15', 'Dell XPS 15 with Intel Core i9, 32GB RAM, 1TB SSD, NVIDIA RTX 3050 Ti, 15.6-inch 4K OLED Touch Display, Platinum Silver', 2199.99, 'uploads/products/Dell XPS 15.png', 12, 1),
('Lenovo ThinkPad X1 Carbon', 'Lenovo ThinkPad X1 Carbon Gen 11 with Intel Core i7, 16GB RAM, 512GB SSD, 14-inch WUXGA Display, Black', 1599.99, 'uploads/products/Lenovo ThinkPad X1.png', 18, 1),
('HP Spectre x360', 'HP Spectre x360 14 with Intel Core i7, 16GB RAM, 1TB SSD, 13.5-inch 3K2K OLED Touch Display, Nightfall Black', 1499.99, 'uploads/products/HP Spectre x360.png', 10, 1),

-- Smartphones Category (2)
('Samsung Galaxy S24 Ultra', 'Samsung Galaxy S24 Ultra with 6.8" Dynamic AMOLED display, 200MP camera, Snapdragon 8 Gen 3, 12GB RAM, 256GB storage, Titanium Black', 1299.99, 'uploads/products/Samsung Galaxy S24 Ultra.png', 30, 2),
('iPhone 15 Pro Max', 'Apple iPhone 15 Pro Max with A17 Pro chip, 8GB RAM, 512GB storage, 6.7-inch Super Retina XDR display, Titanium finish', 1399.99, 'uploads/products/iPhone 15 Pro Max.png', 25, 2),
('Google Pixel 8 Pro', 'Google Pixel 8 Pro with Google Tensor G3, 12GB RAM, 256GB storage, 6.7-inch Super Actua display, Obsidian', 999.99, 'uploads/products/Google Pixel 8 Pro.png', 20, 2),
('OnePlus 12', 'OnePlus 12 with Snapdragon 8 Gen 3, 16GB RAM, 512GB storage, 6.82-inch 2K 120Hz AMOLED display, Flowy Emerald', 899.99, 'uploads/products/OnePlus 12.png', 15, 2),

-- Audio Category (3)
('Sony WH-1000XM5', 'Sony WH-1000XM5 Wireless Noise Cancelling Headphones with Auto Noise Cancelling Optimizer, Crystal Clear Hands-Free Calling, and Alexa Voice Control, Black', 349.99, 'uploads/products/Sony WH-1000XM5.png', 45, 3),
('Bose QuietComfort Ultra', 'Bose QuietComfort Ultra Headphones with Spatial Audio, World-class noise cancellation, Bluetooth, Up to 24 hours battery life, Black', 429.99, 'uploads/products/Bose QuietComfort Ultra.png', 25, 3),
('Apple AirPods Pro 2', 'Apple AirPods Pro 2nd Generation with Active Noise Cancellation, Transparency mode, Spatial Audio, and MagSafe Charging Case', 249.99, 'uploads/products/AirPods Pro 2.png', 40, 3),
('Sonos Arc', 'Sonos Arc Premium Smart Soundbar with Dolby Atmos, Voice Control, and Multi-room Audio, Black', 899.99, 'uploads/products/Sonos Arc.png', 15, 3),
('JBL Flip 6', 'JBL Flip 6 Portable Waterproof Bluetooth Speaker with PartyBoost, 12 Hours of Playtime, and USB-C Charging, Black', 129.99, 'uploads/products/JBL Flip 6.png', 30, 3),

-- Tablets Category (4)
('iPad Pro M2', 'Apple iPad Pro 12.9-inch with M2 chip, Liquid Retina XDR display, 256GB, Wi-Fi 6E, Silver, 2022 model', 1099.99, 'uploads/products/iPad Pro M2.png', 20, 4),
('Samsung Galaxy Tab S9 Ultra', 'Samsung Galaxy Tab S9 Ultra with 14.6-inch Dynamic AMOLED 2X display, Snapdragon 8 Gen 2, 12GB RAM, 256GB storage, Graphite', 1199.99, 'uploads/products/Galaxy Tab S9 Ultra.png', 15, 4),
('Microsoft Surface Pro 9', 'Microsoft Surface Pro 9 with Intel Core i7, 16GB RAM, 256GB SSD, 13-inch PixelSense Flow display, Platinum', 1299.99, 'uploads/products/Surface Pro 9.png', 12, 4),
('Lenovo Tab P12 Pro', 'Lenovo Tab P12 Pro with Snapdragon 870, 8GB RAM, 256GB storage, 12.6-inch 2K AMOLED display, Storm Grey', 699.99, 'uploads/products/Lenovo Tab P12 Pro.png', 18, 4),

-- Gaming Category (5)
('ASUS ROG Strix G17', 'ASUS ROG Strix G17 Gaming Laptop with AMD Ryzen 9 7945HX, NVIDIA GeForce RTX 4080, 32GB DDR5, 1TB PCIe 4.0 SSD, 17.3" QHD 240Hz Display', 2499.99, 'uploads/products/ASUS ROG Strix G17.png', 8, 5),
('PlayStation 5 Pro', 'Sony PlayStation 5 Pro Console with 2TB SSD, 8K support, 120fps gameplay, DualSense controller, and enhanced ray tracing', 699.99, 'uploads/products/PlayStation 5 Pro.png', 10, 5),
('Xbox Series X', 'Microsoft Xbox Series X Console with 1TB SSD, 4K gaming at up to 120fps, Quick Resume, Xbox Wireless Controller, and Xbox Game Pass compatibility', 499.99, 'uploads/products/Xbox Series X.png', 15, 5),
('Razer BlackShark V2 Pro', 'Razer BlackShark V2 Pro Wireless Gaming Headset with HyperSpeed Wireless, TriForce Titanium 50mm Drivers, and Razer Synapse 3 compatibility', 179.99, 'uploads/products/Razer BlackShark V2 Pro.png', 22, 5),
('NVIDIA GeForce RTX 4090', 'NVIDIA GeForce RTX 4090 Founders Edition Graphics Card with 24GB GDDR6X, DLSS 3, ray tracing, and 4K gaming performance', 1599.99, 'uploads/products/NVIDIA RTX 4090.png', 5, 5),

-- Drones Category (6)
('DJI Mini 3 Pro', 'DJI Mini 3 Pro Drone with 4K HDR Video, 48MP Photo, 34-min Flight Time, Tri-Directional Obstacle Sensing, Ideal for Aerial Photography and Social Media', 759.99, 'uploads/products/DJI Mini 3 Pro.png', 12, 6),
('DJI Mavic 3 Pro', 'DJI Mavic 3 Pro with Hasselblad Camera, 4/3 CMOS, 5.1K Video, 46-min Flight Time, Omnidirectional Obstacle Sensing, and O3+ Video Transmission', 2199.99, 'uploads/products/DJI Mavic 3 Pro.png', 8, 6),
('Autel Robotics EVO II Pro', 'Autel Robotics EVO II Pro Drone with 6K HDR Video, 1-inch CMOS Sensor, 40-min Flight Time, 360° Obstacle Avoidance, and 9km Transmission Range', 1795.00, 'uploads/products/Autel EVO II Pro.png', 6, 6),
('Skydio 2+', 'Skydio 2+ Drone with 4K60 HDR Camera, 27-min Flight Time, Autonomous Flight, Sports Mode, and KeyFrame technology for cinematic shots', 1099.00, 'uploads/products/Skydio 2+.png', 10, 6);
