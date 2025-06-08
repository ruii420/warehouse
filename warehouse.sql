-- Create the warehouse database
CREATE DATABASE IF NOT EXISTS warehouse;
USE warehouse;

-- Create roles table
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) UNIQUE NOT NULL,
    can_add_product BOOLEAN DEFAULT FALSE,
    can_add_user BOOLEAN DEFAULT FALSE,
    can_manage_users BOOLEAN DEFAULT FALSE,
    can_create_report BOOLEAN DEFAULT FALSE,
    can_make_order BOOLEAN DEFAULT FALSE,
    can_manage_inventory BOOLEAN DEFAULT FALSE
);

-- Insert default roles
INSERT IGNORE INTO roles (id, role_name, can_add_product, can_add_user, can_manage_users, can_create_report, can_make_order, can_manage_inventory) VALUES
(1, 'Admin', TRUE, TRUE, TRUE, TRUE, TRUE, TRUE),
(2, 'Warehouse Worker', TRUE, FALSE, FALSE, TRUE, TRUE, FALSE),
(3, 'Regular User', FALSE, FALSE, FALSE, FALSE, FALSE, FALSE),
(4, 'Shelf Organizer', FALSE, FALSE, FALSE, FALSE, FALSE, TRUE);


-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role_id INT DEFAULT 3, -- Default to Regular User
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);

-- Create products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    category VARCHAR(50) NOT NULL,
    company_id VARCHAR(50) NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    price DECIMAL(10,2) NOT NULL,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Create inventory_log table
CREATE TABLE IF NOT EXISTS inventory_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    action ENUM('add', 'remove', 'update') NOT NULL,
    quantity INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);
