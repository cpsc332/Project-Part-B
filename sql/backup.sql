USE theatre_booking;
DELIMITER $$

DROP PROCEDURE IF EXISTS backup_full_clone$$
CREATE PROCEDURE backup_full_clone()
BEGIN
	DECLARE backup_prefix VARCHAR(32);
	SET backup_prefix = DATE_FORMAT(CURDATE(), 'back_%Y%m%d_');

	SET @src = 'theatre';
	SET @dst = CONCAT(backup_prefix, @src); 

 	-- Theatre
	SET @sql = CONCAT('DROP TABLE IF EXISTS ', @dst); 
	PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

	SET @sql = CONCAT('CREATE TABLE ', @dst, ' AS SELECT * FROM ', @src); 
	PREPARE stmt from @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt; 

	-- Auditorium
	SET @src = 'auditorium';
	SET @dst = CONCAT(backup_prefix, @src);

	SET @sql = CONCAT('DROP TABLE IF EXISTS ', @dst); 
	PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
	
SET @sql = CONCAT('CREATE TABLE ', @dst, ' AS SELECT * FROM ', @src); 	
	PREPARE stmt from @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt; 

	-- Seat
	SET @src = 'seat'; 
	SET @dst = CONCAT(backup_prefix, @src); 

	SET @sql = CONCAT('DROP TABLE IF EXISTS ', @dst); 
	PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

	SET @sql = CONCAT('CREATE TABLE ', @dst, ' AS SELECT * FROM ', @src); 	
	PREPARE stmt from @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

	-- Movie
	SET @src = 'movie'; 
	SET @dst = CONCAT(backup_prefix, @src); 

	SET @sql = CONCAT('DROP TABLE IF EXISTS ', @dst); 
	PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

	SET @sql = CONCAT('CREATE TABLE ', @dst, ' AS SELECT * FROM ', @src); 	
	PREPARE stmt from @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

	-- Showtime
	SET @src = 'showtime'; 
	SET @dst = CONCAT(backup_prefix, @src); 
	
	SET @sql = CONCAT('DROP TABLE IF EXISTS ', @dst); 
	PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

	SET @sql = CONCAT('CREATE TABLE ', @dst, ' AS SELECT * FROM ', @src); 	
	PREPARE stmt from @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

	-- Customer
	SET @src = 'customer';
	SET @dst = CONCAT(backup_prefix, @src); 
	
	SET @sql = CONCAT('DROP TABLE IF EXISTS ', @dst); 
	PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

	SET @sql = CONCAT('CREATE TABLE ', @dst, ' AS SELECT * FROM ', @src); 	
	PREPARE stmt from @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

	-- Ticket
	SET @src = 'ticket'; 
	SET @dst = CONCAT(backup_prefix, @src);

	SET @sql = CONCAT('DROP TABLE IF EXISTS ', @dst); 
	PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

	SET @sql = CONCAT('CREATE TABLE ', @dst, ' AS SELECT * FROM ', @src); 	
	PREPARE stmt from @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

END$$
DELIMITER ; 

-- Run backup
CALL backup_full_clone();

