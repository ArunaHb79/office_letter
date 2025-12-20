-- Make receiver field nullable for outgoing letters
-- This allows outgoing letters to be created without a receiver value
-- since outgoing letters don't need the old "receiver" field

USE office_letter;

ALTER TABLE Letter 
MODIFY COLUMN receiver VARCHAR(255) NULL;

-- Also make sender nullable for incoming letters
ALTER TABLE Letter 
MODIFY COLUMN sender VARCHAR(255) NULL;
