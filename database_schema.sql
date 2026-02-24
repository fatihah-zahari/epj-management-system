CREATE DATABASE IF NOT EXISTS epj_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE epj_db;

-- USERS
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  fullname VARCHAR(150) NOT NULL,
  ic VARCHAR(30) NULL,
  phone VARCHAR(30) NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','imam','user') NOT NULL DEFAULT 'user',
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL
) ENGINE=InnoDB;

-- SERVICES (requests)
CREATE TABLE kafan_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  deceased_name VARCHAR(150) NOT NULL,
  deceased_ic VARCHAR(30) NULL,
  death_date DATE NULL,
  address TEXT NULL,
  notes TEXT NULL,
  status ENUM('pending','approved','rejected','completed') NOT NULL DEFAULT 'pending',
  decided_by INT NULL,
  decided_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL,
  CONSTRAINT fk_kafan_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE burial_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  deceased_name VARCHAR(150) NOT NULL,
  deceased_ic VARCHAR(30) NULL,
  burial_date DATE NULL,
  cemetery_area VARCHAR(100) NULL,
  plot_no VARCHAR(50) NULL,
  address TEXT NULL,
  notes TEXT NULL,
  status ENUM('pending','approved','rejected','completed') NOT NULL DEFAULT 'pending',
  decided_by INT NULL,
  decided_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL,
  CONSTRAINT fk_burial_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE khairat_claims (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  applicant_name VARCHAR(150) NOT NULL,
  applicant_ic VARCHAR(30) NULL,
  deceased_name VARCHAR(150) NULL,
  death_date DATE NULL,
  bank_name VARCHAR(80) NULL,
  bank_account VARCHAR(50) NULL,
  claim_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  status ENUM('pending','approved','rejected','paid') NOT NULL DEFAULT 'pending',
  receipt_no VARCHAR(100) NULL,
  decided_by INT NULL,
  decided_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL,
  CONSTRAINT fk_khairat_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- INVOICES
CREATE TABLE invoices (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  service_type ENUM('kafan','burial','khairat') NOT NULL,
  service_id INT NOT NULL,
  invoice_no VARCHAR(30) NOT NULL UNIQUE,
  amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  payment_status ENUM('unpaid','paid') NOT NULL DEFAULT 'unpaid',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_invoice_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- AUDIT LOGS
CREATE TABLE audit_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  action VARCHAR(200) NOT NULL,
  meta TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
