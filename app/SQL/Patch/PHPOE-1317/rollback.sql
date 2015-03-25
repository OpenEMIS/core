UPDATE `security_functions` SET `_view` = 'roles|permissions|rolesView' 
WHERE `module` LIKE 'Administration' AND `controller` LIKE 'Security' AND `name` LIKE 'Roles';

