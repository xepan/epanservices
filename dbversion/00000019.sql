ALTER TABLE `task` ADD COLUMN `is_reminder`  tinyint(4) NULL DEFAULT NULL AFTER `notify_to`;