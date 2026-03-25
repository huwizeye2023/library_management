-- Library Management System Database Schema
-- Run this SQL to set up the database

-- Create database
CREATE DATABASE IF NOT EXISTS library_db;
USE library_db;

-- Admins table (for super admin access)
CREATE TABLE IF NOT EXISTS admins (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Librarians table
CREATE TABLE IF NOT EXISTS librarians (
    librarian_id INT AUTO_INCREMENT PRIMARY KEY,
    names VARCHAR(100) NOT NULL,
    telephone VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Members table
CREATE TABLE IF NOT EXISTS members (
    member_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Books table
CREATE TABLE IF NOT EXISTS books (
    book_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    author VARCHAR(100) NOT NULL,
    year_published INT,
    quantity INT DEFAULT 1,
    available_quantity INT DEFAULT 1,
    status ENUM('active', 'inactive', 'archived') DEFAULT 'active',
    content TEXT, -- For online reading
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Borrow table (with approval workflow)
CREATE TABLE IF NOT EXISTS borrow (
    borrow_id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    book_id INT NOT NULL,
    librarian_id INT, -- Who approved/denied
    borrow_date DATE NOT NULL,
    return_date DATE NOT NULL,
    actual_return_date DATE,
    status ENUM('pending', 'approved', 'denied', 'borrowed', 'returned', 'overdue') DEFAULT 'pending',
    request_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(member_id),
    FOREIGN KEY (book_id) REFERENCES books(book_id),
    FOREIGN KEY (librarian_id) REFERENCES librarians(librarian_id)
);

-- Insert default admin (username: admin, password: admin123)
INSERT INTO admins (username, email, password) VALUES 
('admin', 'admin@library.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Insert default librarian (names: John Librarian, password: librarian123)
INSERT INTO librarians (names, telephone, email, password) VALUES 
('John Librarian', '1234567890', 'librarian@library.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Sample books
INSERT INTO books (title, author, year_published, quantity, available_quantity, content) VALUES 
('The Great Gatsby', 'F. Scott Fitzgerald', 1925, 5, 5, 'Full book content for online reading...'),
('To Kill a Mockingbird', 'Harper Lee', 1960, 3, 3, 'Full book content for online reading...'),
('1984', 'George Orwell', 1949, 4, 4, 'Full book content for online reading...'),
('Pride and Prejudice', 'Jane Austen', 1813, 3, 3, 'Full book content for online reading...'),
('The Catcher in the Rye', 'J.D. Salinger', 1951, 2, 2, 'Full book content for online reading...');
