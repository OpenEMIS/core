<?php
use Migrations\AbstractMigration;

class POCOR6698 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_6698_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6698_security_functions` SELECT * FROM `security_functions`');
        
        //Update security functions for Absence delete permission
        $this->execute(
                        'UPDATE `security_functions` SET `_view` = "StudentCompetencies.index|StudentCompetencies.view" 
                        WHERE `name` = "Competencies" AND `controller` = "Profiles"
                        AND `module` = "Personal"
                        AND `category` = "Students - Academic"'
                    );      
    }

    public function down()
    {
        // security_functions
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6698_security_functions` TO `security_functions`');
    }
}
