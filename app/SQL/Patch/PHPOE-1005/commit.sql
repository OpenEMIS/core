UPDATE `navigations` SET `pattern` = 'Area' WHERE `module` = 'Administration' AND `controller` = 'Areas';

UPDATE `security_functions` SET 
`_view` = 'AreaLevel.index|AreaLevel.view|AreaEducationLevel.index|AreaEducationLevel.view',
`_edit` = '_view:AreaLevel.edit|AreaEducationLevel.edit',
`_add` = '_view:AreaLevel.add|AreaEducationLevel.add'
WHERE `name` = 'Area levels' AND `controller` = 'Areas';

UPDATE `security_functions` SET 
`_view` = 'index|Area.index|Area.view|AreaEducation.index|AreaEducation.view',
`_edit` = '_view:Area.edit|Area.reorder|Area.move|AreaEducation.edit|AreaEducation.reorder|AreaEducation.move',
`_add` = '_view:Area.add|AreaEducation.add'
WHERE `name` = 'Areas' AND `controller` = 'Areas';
