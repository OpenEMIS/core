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
        $this->execute('CREATE TABLE `zz_7366_institution_counsellings` LIKE `institution_counsellings`');
        $this->execute('INSERT INTO `zz_7366_institution_counsellings` SELECT * FROM `institution_counsellings`');
        $this->execute('RENAME TABLE `institution_counsellings` TO `counsellings`');


        $this->execute('DROP TABLE IF EXISTS `zz_7366_security_functions`');
        $this->execute('CREATE TABLE `zz_7366_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_7366_security_functions` SELECT * FROM `security_functions`');
        $this->insert('security_functions', [
            'name' => 'Counselling',
            'controller' => 'Directories',
            'module' => 'Directory',
            'category' => 'Counselling',
            'parent_id' => 7000,
            '_view' => 'Counsellings.index|Counsellings.view',
            '_edit'=>'Counsellings.edit',
            '_add'=>'Counsellings.add',
            '_delete'=>'Counsellings.delete',       
            'order' =>352 ,
            'visible' => 1,
            'description' => NULL,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);

    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `counsellings`');
        $this->execute('RENAME TABLE `zz_7366_institution_counsellings` TO `institution_counsellings`');

        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_7366_security_functions` TO `security_functions`');
    }

}