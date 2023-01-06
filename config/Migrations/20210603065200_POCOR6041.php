<?php
use Migrations\AbstractMigration;

class POCOR6041 extends AbstractMigration
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
        //backup
        $this->execute('CREATE TABLE `z_6041_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_6041_security_functions` SELECT * FROM `security_functions`'); 

        //rename module name profile to personal
        $this->execute("UPDATE security_functions SET _view = 'Personal.index|Personal.view', _edit = 'Personal.edit', _add = 'Personal.add', _delete = 'Personal.remove' WHERE name = 'Overview' AND controller = 'Profiles' AND module = 'Profile' AND category = 'General'");
        
        $this->execute("UPDATE security_functions SET module = 'Personal' WHERE module = 'Profile'"); 
    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_6041_security_functions` TO `security_functions`');
    }
}   
