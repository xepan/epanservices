ALTER TABLE 'task' ADD COLUMN `assign_to_id` int (11) NULL DEFAULT NULL AFTER `deadline`;
ALTER TABLE `task` DROP COLUMN 'employee_id';