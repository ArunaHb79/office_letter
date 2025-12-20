-- ============================================================================
-- Make Content Field Optional in Letter Table
-- ============================================================================
-- This script makes the content field optional since letters now use
-- file attachments (images/PDFs) to show the actual letter content
-- Run this in phpMyAdmin or MySQL command line
-- ============================================================================

USE office_letter;

-- Make content column nullable
ALTER TABLE Letter 
MODIFY COLUMN content TEXT NULL;

-- Update fulltext index to exclude content field
-- First drop the existing index
ALTER TABLE Letter DROP INDEX idx_letter_search;

-- Create new fulltext index without content field
ALTER TABLE Letter 
ADD FULLTEXT INDEX idx_letter_search (subject, sender, receiver);

-- Verification query
DESCRIBE Letter;

-- ============================================================================
-- Notes:
-- 1. Content field is now optional (NULL allowed)
-- 2. Users should upload file attachments instead of typing content
-- 3. Fulltext search now works on subject, sender, and receiver only
-- 4. Existing content data is preserved (not deleted)
-- ============================================================================
