-- Fix missing columns in catering_requests table
USE `nrsc_catering`;

ALTER TABLE catering_requests 
ADD COLUMN IF NOT EXISTS guest_count INT DEFAULT 0 AFTER area,
ADD COLUMN IF NOT EXISTS purpose TEXT AFTER guest_count,
ADD COLUMN IF NOT EXISTS special_instructions TEXT AFTER purpose;
