<?php
use Migrations\AbstractMigration;

class POCOR6384 extends AbstractMigration
{
    public function up()
    {
        /** START: security_functions table changes */
        $this->execute('DROP TABLE IF EXISTS `zz_6384_security_functions`');
        $this->execute('CREATE TABLE `zz_6384_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6384_security_functions` SELECT * FROM `security_functions`');

        $this->insert('security_functions', [
            'name' => 'Student Transition',
            'controller' => 'Institutions',
            'module' => 'Directory',
            'category' => 'Students - Academic',
            'parent_id' => 7000,
            '_view' => NULL,
            '_edit' => NULL,
            '_add' => NULL,
            '_delete' => NULL,
            '_execute' => 'StudentTransition.edit',
            'order' => 355,
            'visible' => 1,
            'description' => NULL,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);
        /** END: security_functions table changes */
    }

    //rollback
    public function down()
    {
        /** START: security_functions table changes */
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6384_security_functions` TO `security_functions`');
        /** END: security_functions table changes */
    }
}
