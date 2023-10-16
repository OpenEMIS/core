<?php

use Phinx\Migration\AbstractMigration;

class POCOR4657 extends AbstractMigration
{
    public function up()
    {
        // create backup for security_functions     
        $this->execute('CREATE TABLE `z_4657_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_4657_security_functions` SELECT * FROM `security_functions`');

        //Updates all the order by +1
        $this->execute('UPDATE security_functions SET `name` = "Audits", `_view` = "Audits.index", `_add` = "Audits.add", `_execute` = "Audits.download" WHERE `id` = 6007');
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_4657_security_functions` TO `security_functions`');
    }
}
