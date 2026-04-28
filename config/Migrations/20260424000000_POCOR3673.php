<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR3673 extends AbstractMigration
{
    public function up(): void
    {
        // import_mapping: two columns for the Translations import template
        $this->execute("INSERT INTO `import_mapping` (`model`, `column_name`, `description`, `order`, `is_optional`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) VALUES ('System.LocaleContentsLanguage', 'en', '', 1, 0, 0, NULL, NULL, NULL)");
        $this->execute("INSERT INTO `import_mapping` (`model`, `column_name`, `description`, `order`, `is_optional`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) VALUES ('System.LocaleContentsLanguage', 'translation', '', 2, 0, 0, NULL, NULL, NULL)");

        // security_functions: allow admins to access the import action
        $this->execute("INSERT INTO `security_functions` (`name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES ('Import Translations', 'LocaleContents', 'Administration', 'Localization', 5000, NULL, NULL, NULL, NULL, 'ImportLocaleContentsLanguage.add|ImportLocaleContentsLanguage.template|ImportLocaleContentsLanguage.results|ImportLocaleContentsLanguage.downloadFailed|ImportLocaleContentsLanguage.downloadPassed', 262, 1, NULL, NULL, NULL, 1, NOW())");
    }

    public function down(): void
    {
        $this->execute("DELETE FROM `import_mapping` WHERE `model` = 'System.LocaleContentsLanguage'");
        $this->execute("DELETE FROM `security_functions` WHERE `name` = 'Import Translations' AND `controller` = 'LocaleContents'");
    }
}
