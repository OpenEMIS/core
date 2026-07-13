<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR3673 extends AbstractMigration
{
    public function up(): void
    {
        // import_mapping: Label -> locale_contents.en, Locale -> locale_id, Translated Label -> translation
        $this->execute("DELETE FROM `import_mapping` WHERE `model` = 'System.LocaleContentsLanguage'");

        $this->execute("INSERT INTO `import_mapping` (`model`, `column_name`, `description`, `order`, `is_optional`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) VALUES
            ('System.LocaleContentsLanguage', 'label', '', 1, 0, 0, NULL, NULL, NULL),
            ('System.LocaleContentsLanguage', 'locale_iso', '', 2, 0, 2, 'System', 'Locales', 'id'),
            ('System.LocaleContentsLanguage', 'translated_label', '', 3, 0, 0, NULL, NULL, NULL)");

        // security_functions: allow admins to access the import action
        $this->execute("INSERT INTO `security_functions` (`name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES ('Import Translations', 'LocaleContents', 'Administration', 'Localization', 5000, NULL, NULL, NULL, NULL, 'ImportLocaleContentsLanguage.add|ImportLocaleContentsLanguage.template|ImportLocaleContentsLanguage.results|ImportLocaleContentsLanguage.downloadFailed|ImportLocaleContentsLanguage.downloadPassed', 262, 1, NULL, NULL, NULL, 1, NOW())");
    }

    public function down(): void
    {
        $this->execute("DELETE FROM `import_mapping` WHERE `model` = 'System.LocaleContentsLanguage'");
        $this->execute("DELETE FROM `security_functions` WHERE `name` = 'Import Translations' AND `controller` = 'LocaleContents'");
    }
}
