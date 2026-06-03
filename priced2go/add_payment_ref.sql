-- Run this in phpMyAdmin → priced2go_db → SQL tab
-- It adds the payment_ref column needed for PayFast tracking

ALTER TABLE orders ADD COLUMN payment_ref VARCHAR(100) AFTER status;
