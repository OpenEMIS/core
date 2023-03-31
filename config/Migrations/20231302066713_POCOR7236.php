<?php

use Phinx\Migration\AbstractMigration;

/**
 * POCOR-7236
 * unique code validation
**/
class POCOR7236 extends AbstractMigration
{
    // commit
    public function up()
    {
        // Backup table
        $this->execute('CREATE TABLE `zz_7236_security_roles` LIKE `security_roles`');
        $this->execute('INSERT INTO `zz_7236_security_roles` SELECT * FROM `security_roles`');

        $this->execute("ALTER TABLE `security_roles` ADD UNIQUE (`code`)");
    }

    // rollback
    public function down()
    {
        // Restore table
        $this->execute('DROP TABLE IF EXISTS `security_roles`');
        $this->execute('RENAME TABLE `zz_7236_security_roles` TO `security_roles`');
    }
}

?>
