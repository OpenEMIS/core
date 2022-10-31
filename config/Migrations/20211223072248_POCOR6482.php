<?php
use Migrations\AbstractMigration;

class POCOR6482 extends AbstractMigration
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
        // Creating backup
        $this->execute('DROP TABLE IF EXISTS `zz_6482_security_functions`');
        $this->execute('CREATE TABLE `zz_6482_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6482_security_functions` SELECT * FROM `security_functions`');

        // Getting max order value
        $data = $this->fetchRow("SELECT  max(`order`) FROM `security_functions`");

        // Inserting record
        $this->insert('security_functions', [
            'name' => 'Meals Programme',
            'controller' => 'Meals',
            'module' => 'Administration',
            'category' => 'Meals',
            'parent_id' => 5000,
            '_view' => 'programme.index|programme.view',
            '_edit' => 'programme.edit',
            '_add' => 'programme.add',
            '_delete' => 'programme.remove',
            'order' => $data[0] + 1,
            'visible' => 1,
            'description' => NULL,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);
    }

    // Rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6482_security_functions` TO `security_functions`');
    }
}
