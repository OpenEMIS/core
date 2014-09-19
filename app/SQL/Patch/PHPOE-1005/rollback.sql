UPDATE `navigations` SET `pattern` = 'index$|levels|edit|AreaEducation|Area' WHERE `module` = 'Administration' AND `controller` = 'Areas';

UPDATE `security_functions` SET 
`_view` = 'index|levels|levelsView|AreaEducationLevels',
`_edit` = 'levelsEdit|AreaEducationLevelsEdit',
`_add` = 'levelsAdd'
WHERE `name` = 'Area levels' AND `controller` = 'Areas';

UPDATE `security_functions` SET 
`_view` = 'Area.index|Area.view|index|areas|areasView|areasEducation|areasEducationView',
`_edit` = 'Area.edit|areasEdit|areasReorder|areasMove|areasEducationEdit|areasEducationReorder|areasEducationMove',
`_add` = '_view:Area.add|areasAdd|areasEducationAdd'
WHERE `name` = 'Areas' AND `controller` = 'Areas';
