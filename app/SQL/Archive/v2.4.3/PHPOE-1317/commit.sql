UPDATE `security_functions` SET `_view` = 'roles|rolesUserDefined|permissions|rolesView', 
`_edit` = '_view:rolesEdit|permissionsEdit|rolesReorder|rolesMove' 
WHERE `module` LIKE 'Administration' AND `controller` LIKE 'Security' AND `name` LIKE 'Roles';