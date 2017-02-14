-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3605', NOW());

-- security_functions
UPDATE `security_functions` SET `order` = 5056 WHERE `id` = 5009;

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(5056, 'Education Grade Subjects', 'Educations', 'Administration', 'Education', 5000, 'GradeSubjects.index|GradeSubjects.view', 'GradeSubjects.edit', 'GradeSubjects.add', 'GradeSubjects.remove', NULL, 5009, 1, NULL, NULL, NULL, 1, NOW());
