UPDATE `security_functions` SET `_view` = 'roles|permissions|rolesView' 
`_edit` = '_view:rolesEdit|permissionsEdit' 
WHERE `module` LIKE 'Administration' AND `controller` LIKE 'Security' AND `name` LIKE 'Roles';

