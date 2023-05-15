<?php
use Migrations\AbstractMigration;

class POCOR7366 extends AbstractMigration
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
        //$this->execute('DROP TABLE IF EXISTS `zz_6515_security_functions`');
        $this->execute('CREATE TABLE `zz_7366_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_7366_security_functions` SELECT * FROM `security_functions`');

        $data = $this->fetchRow("SELECT `order`,`parent_id` FROM `security_functions` WHERE `name` = 'Generate Students Profile' AND `controller` = 'Directories' AND `module` = 'Directory' AND `category` = 'Profiles' ");

        $this->insert('security_functions', [
            'name' => 'Counselling',
            'controller' => 'Directories',
            'module' => 'Directory',
            'category' => 'Counselling',
            'parent_id' => $data[1],
            '_view' => 'StudentCounselling.index|StudentCounselling.view',
            '_execute' => 'StudentCounselling.download',            
            'order' => $data[0] + 1,
            'visible' => 1,
            'description' => NULL,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);

    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_7366_security_functions` TO `security_functions`');
    }

}