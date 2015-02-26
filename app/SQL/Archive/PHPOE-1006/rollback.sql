UPDATE `navigations` SET `action` = 'programmes', `pattern` = 'programmes' WHERE `controller` = 'InstitutionSites' AND `title` = 'Programmes';

UPDATE `security_functions` SET `_view` = 'programmes', `_edit` = '_view:programmesEdit' WHERE `controller` = 'InstitutionSites' AND `category` = 'Details' AND `name` = 'Programmes';