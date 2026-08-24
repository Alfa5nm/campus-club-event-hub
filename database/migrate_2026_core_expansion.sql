USE campus_club_hub;

SET @has_notified_at = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='announcement' AND column_name='notified_at');
SET @sql = IF(@has_notified_at=0, 'ALTER TABLE announcement ADD COLUMN notified_at DATETIME NULL AFTER status', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
