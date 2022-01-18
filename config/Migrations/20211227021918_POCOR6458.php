<?php
use Migrations\AbstractMigration;

class POCOR6458 extends AbstractMigration
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
        // Backup Table
        $this->execute('CREATE TABLE `z_6458_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_6458_security_functions` SELECT * FROM `security_functions`');
        
        //Update security functions for Absence delete permission
        $this->execute(
                        'UPDATE `security_functions` SET `category` = "General" 
                        WHERE `name` = "Shifts" AND `controller` = "Institutions"
                        AND `module` = "Institutions"'
                    );      
    }

    public function down()
    {
        // security_functions
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_6458_security_functions` TO `security_functions`');
        
    }
}
