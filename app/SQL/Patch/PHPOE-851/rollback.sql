UPDATE `security_functions` SET `_view` = 'studentsBehaviour|studentsBehaviourView',
`_edit` = '_view:studentsBehaviourEdit',
`_add` = '_view:studentsBehaviourAdd',
`_delete` = '_view:studentsBehaviourDelete' WHERE `security_functions`.`id` =21;

UPDATE `security_functions` SET `_view` = 'staffBehaviour|staffBehaviourView',
`_edit` = '_view:staffBehaviourEdit',
`_add` = '_view:staffBehaviourAdd',
`_delete` = '_view:staffBehaviourDelete' WHERE `security_functions`.`id` =103;

DELETE FROM `navigations` WHERE `navigations`.`id` = 143 LIMIT 1;
DELETE FROM `navigations` WHERE `navigations`.`id` = 144 LIMIT 1;