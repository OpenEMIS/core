UPDATE `security_functions` SET 
`_view` = 'roles|permissions|rolesView', 
`_add` = '_view:rolesAdd' 
WHERE `controller` LIKE 'Security' AND `module` LIKE 'Administration' AND `name` LIKE 'Roles';