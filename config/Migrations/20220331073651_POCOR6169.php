<?php
use Migrations\AbstractMigration;

class POCOR6169 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_6169_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6169_security_functions` SELECT * FROM `security_functions`');
        
        //Update security functions for Absence delete permission
        $this->execute(
                        'UPDATE `security_functions` SET `controller` = "Institutions", `_view` = "InstitutionTrips.index|InstitutionTrips.view", `_edit` = "InstitutionTrips.edit", `_add` ="InstitutionTrips.add", `_delete`="InstitutionTrips.delete", `_execute`="InstitutionTrips.excel" 
                        WHERE `name` = "Trips" AND `controller` = "InstitutionTrips"
                        AND `module` = "Institutions"
                        AND `category` = "Transport"'
                    );      
    }

    public function down()
    {
        // security_functions
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6169_security_functions` TO `security_functions`');
    }
}
