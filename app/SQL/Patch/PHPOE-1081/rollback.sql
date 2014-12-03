UPDATE `navigations` SET `module` = 'Home', `controller` = 'Home', `title` = 'My Details', `action` = 'details', `pattern` = 'details' WHERE `controller` = 'Preferences' AND `action` = 'account';
UPDATE `navigations` SET `module` = 'Home', `controller` = 'Home', `title` = 'Change Password' WHERE `controller` = 'Preferences' AND `action` = 'password';
