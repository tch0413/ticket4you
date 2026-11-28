ALTER TABLE payment_transactions
 MODIFY COLUMN method ENUM('fpx','card','ewallet','free','duitnow_qr') NOT NULL,
 ADD COLUMN provider VARCHAR(40) NULL AFTER method;
