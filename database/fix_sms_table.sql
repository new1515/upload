-- Run this SQL to fix the sms_settings table columns
-- Go to phpMyAdmin > select database > SQL tab > paste and run

-- Drop old table and recreate (backup your data first!)
-- Or run these ALTER commands:

ALTER TABLE sms_settings 
ADD COLUMN IF NOT EXISTS momo_provider VARCHAR(50) DEFAULT 'mtn',
ADD COLUMN IF NOT EXISTS mtn_momo_subscription_key VARCHAR(200) DEFAULT '',
ADD COLUMN IF NOT EXISTS mtn_momo_primary_key VARCHAR(200) DEFAULT '',
ADD COLUMN IF NOT EXISTS mtn_momo_user_id VARCHAR(100) DEFAULT '',
ADD COLUMN IF NOT EXISTS momo_enabled TINYINT(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS default_country_code VARCHAR(10) DEFAULT '+233',
ADD COLUMN IF NOT EXISTS cost_per_sms DECIMAL(10,4) DEFAULT 0.05;

-- If the above fails (old MySQL), run one at a time:
-- ALTER TABLE sms_settings ADD COLUMN momo_provider VARCHAR(50) DEFAULT 'mtn';
-- ALTER TABLE sms_settings ADD COLUMN mtn_momo_subscription_key VARCHAR(200) DEFAULT '';
-- ALTER TABLE sms_settings ADD COLUMN mtn_momo_primary_key VARCHAR(200) DEFAULT '';
-- ALTER TABLE sms_settings ADD COLUMN mtn_momo_user_id VARCHAR(100) DEFAULT '';
-- ALTER TABLE sms_settings ADD COLUMN momo_enabled TINYINT(1) DEFAULT 0;
-- ALTER TABLE sms_settings ADD COLUMN default_country_code VARCHAR(10) DEFAULT '+233';
-- ALTER TABLE sms_settings ADD COLUMN cost_per_sms DECIMAL(10,4) DEFAULT 0.05;

-- Ensure default row exists
INSERT IGNORE INTO sms_settings (id) VALUES (1);
