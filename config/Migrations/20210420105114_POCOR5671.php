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
        $this->execute('DROP TABLE IF EXISTS `zz_5671_security_functions`');
        $this->execute('CREATE TABLE `zz_5671_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_5671_security_functions` SELECT * FROM `security_functions`');

        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 175');
        //drop previous record
        $this->execute("DELETE FROM security_functions WHERE name='Student Transition'");
        //insert latest record
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
            '_execute' => 'StudentTransition.edit',
            'order' => 176,
            'visible' => 1,
            'description' => NULL,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);

        //update locale_contents table
        $this->execute("UPDATE locale_contents SET en='Transition' WHERE en = 'transition'");
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_5671_security_functions` TO `security_functions`');
        $this->execute('UPDATE security_functions SET `order` = `order` - 1 WHERE `order` > 175');
    }
}
