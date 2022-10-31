<?php
use Phinx\Migration\AbstractMigration;

class POCOR4919 extends AbstractMigration
{
    public function up()
    {
        // Create backup for security_functions and security_role_functions
        $this->execute('CREATE TABLE `z_4919_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_4919_security_functions` SELECT * FROM `security_functions`');

        $sql = 'UPDATE `security_functions` SET
            `_execute` = "InstitutionStaffAttendances.excel"
            WHERE `id` = 1018';
        $this->execute($sql);
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_4919_security_functions` TO `security_functions`');
    }
}
