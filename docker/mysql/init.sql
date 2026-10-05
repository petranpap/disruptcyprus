-- Separate database for the Pest test suite.
CREATE DATABASE IF NOT EXISTS disrupt_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON disrupt_testing.* TO 'disrupt'@'%';
