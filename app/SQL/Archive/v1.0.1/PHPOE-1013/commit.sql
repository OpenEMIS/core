UPDATE `navigations` SET `action` = 'Position', `pattern` = 'Position' WHERE `controller` = 'Staff' AND `title` = 'Positions';

SET @ordering := 0;
SET @maxId := 0;
SET @parentId := 0;
SELECT `order` + 1, `parent_id` into @ordering, @parentId FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Training' AND `category` = 'Details';
SELECT MAX(`id`) + 1 INTO @maxId FROM `security_functions`;
UPDATE `security_functions` SET `order` = `order` + 1 WHERE `order` >= @ordering;
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`) VALUES
(@maxId, 'Positions', 'Staff', 'Staff', 'Details', @parentId, 'Position|Position.index|Position.view', '_view:Position.edit', NULL, '_view:Programme.remove', NULL, @ordering, 1, 1, NOW());
