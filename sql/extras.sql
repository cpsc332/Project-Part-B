USE theatre_booking;


CREATE TABLE IF NOT EXISTS gift_code (
    GiftCodeID INT PRIMARY KEY AUTO_INCREMENT,
    Code VARCHAR(64) NOT NULL UNIQUE,
    Description VARCHAR(255),
    DiscountPercent DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    BundleMinTickets INT NOT NULL DEFAULT 1,
    MaxUses INT NULL,
    Uses INT NOT NULL DEFAULT 0,
    Active BOOLEAN NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO gift_code (Code, Description, DiscountPercent, BundleMinTickets, MaxUses)
VALUES
  ('FAMILY4', '20% off when you buy 4+ tickets', 20.0, 4, NULL),
  ('GIFT10',  '10% off any order',               10.0, 1, NULL);

INSERT INTO customer (Name, Email, GuestFlag, PasswordHash, Role)
VALUES (
  'Admin User',
  'admin@example.com',
  0,
  SHA2('admin123', 256),
  'admin'
)
ON DUPLICATE KEY UPDATE
  PasswordHash = VALUES(PasswordHash),
  Role = 'admin';
