<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR3673 extends AbstractMigration
{
    public function up(): void
    {
        //Backup original table
        $this->execute('CREATE TABLE `zz_3673_import_mapping` LIKE `import_mapping`');
        $this->execute('INSERT INTO `zz_3673_import_mapping` SELECT * FROM `import_mapping`');

        $this->execute('CREATE TABLE `zz_3673_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_3673_security_functions` SELECT * FROM `security_functions`');

        $this->execute("INSERT INTO `import_mapping` (`model`, `column_name`, `description`, `order`, `is_optional`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) VALUES
            ('System.LocaleContentsLanguage', 'label', '', 1, 0, 0, NULL, NULL, NULL),
            ('System.LocaleContentsLanguage', 'locale_iso', '', 2, 0, 2, 'System', 'Locales', 'id'),
            ('System.LocaleContentsLanguage', 'translated_label', '', 3, 0, 0, NULL, NULL, NULL)");

        // security_functions: allow to access the import action
        $row = $this->fetchRow("
            SELECT
                MAX(`order`) AS max_order,
                MAX(`parent_id`) AS max_parent_id
            FROM `security_functions`
            WHERE `module` = 'Administration'
              AND `category` = 'Localization'
        ");

        $order = $row['max_order'] + 1;
        $parentId = $row['max_parent_id'];

        $record = [
            [
                'name' => 'Import Translations',
                'controller' => 'LocaleContents',
                'module' => 'Administration',
                'category' => 'Localization',
                'parent_id' => $parentId,
                '_view' => null,
                '_edit' => null,
                '_add' => null,
                '_delete' => null,
                '_execute' => 'ImportLocaleContentsLanguage.add|ImportLocaleContentsLanguage.template|ImportLocaleContentsLanguage.results|ImportLocaleContentsLanguage.downloadFailed|ImportLocaleContentsLanguage.downloadPassed',
                'order' => $order,
                'visible' => 1,
                'description' => null,
                'modified_user_id' => null,
                'modified' => null,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s'),
            ]
        ];

        $this->table('security_functions')->insert($record)->save();
    }

   public function down(): void
    {
        $this->execute('DROP TABLE IF EXISTS `import_mapping`');
        $this->execute('RENAME TABLE `zz_3673_import_mapping` TO `import_mapping`');
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_3673_security_functions` TO `security_functions`');
    }
}
