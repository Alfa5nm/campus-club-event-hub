USE campus_club_hub;

SET @has_notified_at = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='announcement' AND column_name='notified_at');
SET @sql = IF(@has_notified_at=0, 'ALTER TABLE announcement ADD COLUMN notified_at DATETIME NULL AFTER status', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_gallery_status = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='club_gallery' AND column_name='status');
SET @sql = IF(@has_gallery_status=0, "ALTER TABLE club_gallery ADD COLUMN status ENUM('Active','Removed') NOT NULL DEFAULT 'Active' AFTER caption", 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_source_type = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='notification' AND column_name='source_type');
SET @sql = IF(@has_source_type=0, 'ALTER TABLE notification ADD COLUMN source_type VARCHAR(50) NULL AFTER is_read, ADD COLUMN source_id BIGINT UNSIGNED NULL AFTER source_type', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_source_unique = (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='notification' AND index_name='uq_notification_source');
SET @sql = IF(@has_source_unique=0, 'ALTER TABLE notification ADD UNIQUE KEY uq_notification_source (recipient_user_id, notification_type, source_type, source_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
