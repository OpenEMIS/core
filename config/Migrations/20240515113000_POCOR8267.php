<?php

use Phinx\Migration\AbstractMigration;

class POCOR8267 extends AbstractMigration
{
    public function up()
    {
        //backup
        $this->execute('CREATE TABLE `z_8267_security_role_functions` LIKE `security_role_functions`');
        $this->execute('INSERT INTO `z_8267_security_role_functions` SELECT * FROM `security_role_functions`');

        //enable Execute checkbox for export and import data
        $this->execute("UPDATE security_role_functions INNER JOIN security_roles ON security_roles.id = security_role_functions.security_role_id INNER JOIN security_functions ON security_functions.id = security_role_functions.security_function_id SET security_role_functions._edit = 0 WHERE security_functions.name IN ('accounts','Account Username','Guardian Accounts') AND security_functions.module IN ('institutions','directory','administration','guardian') AND security_role_functions._edit = 1");
    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_role_functions`');
        $this->execute('RENAME TABLE `z_8267_security_role_functions` TO `security_role_functions`');
    }
}
