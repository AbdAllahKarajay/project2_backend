-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS beti_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Grant permissions to the user
GRANT ALL PRIVILEGES ON focus_db.* TO 'focus_user'@'%';
FLUSH PRIVILEGES;

-- Use the database
USE focus_db;

-- Optional: Create any initial tables or data here
-- The Laravel migrations will handle the actual table creation