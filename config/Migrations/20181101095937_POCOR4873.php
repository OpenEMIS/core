<?php

use Phinx\Migration\AbstractMigration;

class POCOR4873 extends AbstractMigration
{
    public function up()
    {
        //backup the table
        $this->execute('CREATE TABLE `z_4873_import_mapping` LIKE `import_mapping`');
        $this->execute('INSERT INTO `z_4873_import_mapping` SELECT * FROM `import_mapping`');
        // end backup


        //insert for import excel column for contact options and contact
        $this->execute('
            INSERT INTO `import_mapping` (`model`, `column_name`, `description`, `order`, `is_optional`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`)
            VALUES ("User.Users", "contact_type", NULL, 17, 0, 2, "User", "ContactTypes", "id")
        ');

        $this->execute('
            INSERT INTO `import_mapping` (`model`, `column_name`, `description`, `order`, `is_optional`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`)
            VALUES ("User.Users", "contact", NULL, 18, 0, 0, NULL, NULL, NULL)
        ');

    }

    public function down()
    {
        // restore the backup table
        $this->execute('DROP TABLE IF EXISTS `import_mapping`');
        $this->execute('RENAME TABLE `z_4873_import_mapping` TO `import_mapping`');
        // end restore

    }
}
