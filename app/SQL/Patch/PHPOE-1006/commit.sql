UPDATE `navigations` SET `action` = 'InstitutionSiteProgramme', `pattern` = 'InstitutionSiteProgramme' WHERE `controller` = 'InstitutionSites' AND `title` = 'Programmes';

UPDATE `security_functions` SET `_view` = 'InstitutionSiteProgramme|InstitutionSiteProgramme.index', `_edit` = '_view:InstitutionSiteProgramme.edit' WHERE `controller` = 'InstitutionSites' AND `category` = 'Details' AND `name` = 'Programmes';
