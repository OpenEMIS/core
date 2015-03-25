UPDATE `security_functions` SET `_view` = 'roles|roles_user_defined|permissions|rolesView' 
WHERE `module` LIKE 'Administration' AND `controller` LIKE 'Security' AND `name` LIKE 'Roles';