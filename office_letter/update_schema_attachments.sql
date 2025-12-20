-- ============================================================================
-- Add Attachment Support to Letter Table
-- ============================================================================
-- This script adds file attachment capability to the Letter table
-- Run this in phpMyAdmin or MySQL command line
-- ============================================================================

USE office_letter;

-- Add attachment columns to Letter table
ALTER TABLE Letter 
ADD COLUMN attachment_filename VARCHAR(255) NULL AFTER content,
ADD COLUMN attachment_path VARCHAR(500) NULL AFTER attachment_filename,
ADD COLUMN attachment_uploaded_at TIMESTAMP NULL AFTER attachment_path;

-- Add index for faster attachment queries
ALTER TABLE Letter
ADD INDEX idx_letter_attachment (attachment_filename);

-- Show updated table structure
DESCRIBE Letter;

-- ============================================================================
-- Verification Query
-- ============================================================================
SELECT 
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = 'office_letter'
AND TABLE_NAME = 'Letter'
AND COLUMN_NAME LIKE 'attachment%';

-- ============================================================================
-- Notes:
-- 1. attachment_filename: Stores the original filename uploaded by user
-- 2. attachment_path: Stores the server path/unique filename
-- 3. attachment_uploaded_at: Timestamp when file was uploaded
-- 4. All columns are NULL to allow existing records without attachments
-- ============================================================================
