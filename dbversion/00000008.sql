ALTER TABLE `emailsetting` ADD COLUMN `is_active`  tinyint(4) NULL DEFAULT NULL AFTER `email_password`;
ALTER TABLE `emailsetting` ADD COLUMN `per_minute`  int(11) NULL DEFAULT NULL AFTER `signature`;
ALTER TABLE `emailsetting` ADD COLUMN `per_month`  int(11) NULL DEFAULT NULL AFTER `per_minute`;