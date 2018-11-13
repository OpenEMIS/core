<?php

use Phinx\Migration\AbstractMigration;

class POCOR4784 extends AbstractMigration
{
    public function up()
    {
        $this->execute('CREATE TABLE `z_4784_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_4784_security_functions` SELECT * FROM `security_functions`');

        // 5102 - RecipientPaymentStructures table
        $this->execute('UPDATE `security_functions` SET `_execute` = "RecipientPaymentStructures.excel" WHERE `id` = 5102');
    }

    public function down()
    {
        $this->execute('DROP TABLE security_functions');
        $this->table('z_4784_security_functions')->rename('scholarships');
    }
}
