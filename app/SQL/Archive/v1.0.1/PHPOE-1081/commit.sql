UPDATE `navigations` SET `module` = 'Preferences', `controller` = 'Preferences', `title` = 'Account', `action` = 'account', `pattern` = 'account' WHERE `controller` = 'Home' AND `action` = 'details';
UPDATE `navigations` SET `module` = 'Preferences', `controller` = 'Preferences', `title` = 'Password' WHERE `controller` = 'Home' AND `action` = 'password';
