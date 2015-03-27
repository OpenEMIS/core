UPDATE `security_functions` SET `_view` = 'roles|permissions', `_add` = '_edit:rolesAdd' 
WHERE `controller` LIKE 'Security' AND `module` LIKE 'Administration' AND `name` LIKE 'Roles';