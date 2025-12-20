-- ============================================================================
-- Multiple Attachments and Letter Direction System
-- Purpose: Support multiple attachments per letter and incoming/outgoing letters
-- Date: 2025-12-20
-- ============================================================================

USE office_letter;

-- ============================================================================
-- 1. Create Letter Attachments Table (Multiple attachments per letter)
-- ============================================================================
CREATE TABLE IF NOT EXISTS LetterAttachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    letter_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL COMMENT 'Original filename',
    file_path VARCHAR(255) NOT NULL COMMENT 'Stored filename',
    file_size INT NOT NULL COMMENT 'File size in bytes',
    file_type VARCHAR(50) NOT NULL COMMENT 'File extension',
    attachment_label VARCHAR(100) NULL COMMENT 'Label: Original Letter, Reply, Supporting Doc, etc.',
    uploaded_by INT NOT NULL COMMENT 'User who uploaded',
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (letter_id) REFERENCES Letter(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES Users(id) ON DELETE CASCADE,
    INDEX idx_letter_attachments (letter_id),
    INDEX idx_uploaded_by (uploaded_by),
    INDEX idx_uploaded_at (uploaded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Multiple attachments per letter';

-- ============================================================================
-- 2. Add Letter Direction/Type Field
-- ============================================================================
ALTER TABLE Letter 
ADD COLUMN IF NOT EXISTS letter_direction ENUM('incoming', 'outgoing_institution', 'outgoing_officer') 
DEFAULT 'incoming' 
COMMENT 'Letter direction: incoming, outgoing from institution, or outgoing from officer'
AFTER letter_number;

-- ============================================================================
-- 3. Add Recipient Information for Outgoing Letters
-- ============================================================================
ALTER TABLE Letter 
ADD COLUMN IF NOT EXISTS recipient_organization VARCHAR(255) NULL 
COMMENT 'Organization receiving the letter (for outgoing letters)'
AFTER receiver;

ALTER TABLE Letter 
ADD COLUMN IF NOT EXISTS recipient_person VARCHAR(255) NULL 
COMMENT 'Person receiving the letter (for outgoing letters)'
AFTER recipient_organization;

ALTER TABLE Letter 
ADD COLUMN IF NOT EXISTS reference_letter_number VARCHAR(100) NULL 
COMMENT 'Reference to incoming letter if this is a reply'
AFTER recipient_person;

-- ============================================================================
-- 4. Create indexes for better performance
-- ============================================================================
CREATE INDEX IF NOT EXISTS idx_letter_direction ON Letter(letter_direction);
CREATE INDEX IF NOT EXISTS idx_reference_letter ON Letter(reference_letter_number);

-- ============================================================================
-- 5. Migrate existing attachments to new table (if any exist)
-- ============================================================================
-- First, find a valid user ID to use as default
SET @default_user_id = (SELECT MIN(id) FROM Users LIMIT 1);

INSERT INTO LetterAttachments (letter_id, file_name, file_path, file_size, file_type, attachment_label, uploaded_by, uploaded_at)
SELECT 
    l.id,
    l.attachment_filename,
    l.attachment_path,
    COALESCE(LENGTH(l.attachment_path) * 1024, 0) as file_size, -- Approximate
    SUBSTRING_INDEX(l.attachment_filename, '.', -1) as file_type,
    'Original Document' as attachment_label,
    COALESCE(
        (SELECT u.id FROM Users u WHERE u.employee_id = l.employee_id LIMIT 1),
        @default_user_id
    ) as uploaded_by,
    l.date_received as uploaded_at
FROM Letter l
WHERE l.attachment_filename IS NOT NULL 
AND l.attachment_filename != ''
AND l.attachment_path IS NOT NULL
AND l.attachment_path != ''
AND NOT EXISTS (
    SELECT 1 FROM LetterAttachments la 
    WHERE la.letter_id = l.id 
    AND la.file_name COLLATE utf8mb4_general_ci = l.attachment_filename COLLATE utf8mb4_general_ci
);

-- ============================================================================
-- Success Message
-- ============================================================================
SELECT 'Multiple attachments system created successfully!' AS Status,
       COUNT(*) as 'Migrated Attachments'
FROM LetterAttachments;

SELECT '✓ You can now:' AS Features UNION ALL
SELECT '  - Upload multiple attachments per letter' UNION ALL
SELECT '  - Label each attachment (Original Letter, Reply, etc.)' UNION ALL
SELECT '  - Track incoming and outgoing letters' UNION ALL
SELECT '  - Record letters sent by institution or officers' UNION ALL
SELECT '  - Reference original letters in replies';
