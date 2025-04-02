-- Update admin user with new hashed password (password: admin)
UPDATE `users` 
SET `password` = '$2y$10$Hy5Qz0CXgWGRHxKVXhJQEOGTDEZWj5D.lJ1YJwE.Ij/2YPxYwQGOi' 
WHERE `email` = 'admin@warehouse.com';

-- If admin user doesn't exist, create it
INSERT INTO `users` (`fname`, `lname`, `email`, `password`, `role`, `created_at`)
SELECT 'Admin', 'User', 'admin@warehouse.com', '$2y$10$Hy5Qz0CXgWGRHxKVXhJQEOGTDEZWj5D.lJ1YJwE.Ij/2YPxYwQGOi', 'admin', CURRENT_TIMESTAMP
WHERE NOT EXISTS (SELECT 1 FROM `users` WHERE `email` = 'admin@warehouse.com');