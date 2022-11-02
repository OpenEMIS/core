<?php

use Phinx\Migration\AbstractMigration;

class POCOR4419 extends AbstractMigration
{
    public function up()
    {
        $this->execute('CREATE TABLE `zz_4419_import_mapping` LIKE `import_mapping`');
        $this->execute('INSERT INTO `zz_4419_import_mapping` SELECT * FROM `import_mapping`');

        $this->execute('UPDATE `import_mapping` SET `description` = "Code" WHERE `id` = 110 AND model="User.Users" AND column_name = "identity_type_id"');
		
		$this->execute('UPDATE `import_mapping` SET `description` = "Id" WHERE `id` = 109 AND model="User.Users" AND column_name = "nationality_id"');
    }

    public function down()
    {
        $this->execute('UPDATE `import_mapping` SET `description` = "Code (Optional)" WHERE `id` = 110 AND model="User.Users" AND column_name = "identity_type_id"');
		
		$this->execute('UPDATE `import_mapping` SET `description` = "Id (Optional)" WHERE `id` = 109 AND model="User.Users" AND column_name = "nationality_id"');
    }
}
