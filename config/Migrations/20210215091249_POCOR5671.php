<?php
use Migrations\AbstractMigration;

class POCOR5671 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {
        $this->execute('CREATE TABLE `zz_5671_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_5671_security_functions` SELECT * FROM `security_functions`');

        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 175');

        $this->insert('security_functions', [
            'name' => 'Student Transition',
            'controller' => 'Institutions',
            'module' => 'Institutions',
            'category' => 'Students - Academic',
            'parent_id' => 2000,
            '_view' => NULL,
            '_edit' => NULL,
            '_add' => NULL,
            '_delete' => NULL,
            '_execute' => 'execute',
            'order' => 176,
            'visible' => 1,
            'description' => NULL,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_5671_security_functions` TO `security_functions`');
        $this->execute('UPDATE security_functions SET `order` = `order` - 1 WHERE `order` > 175');
    }
}
