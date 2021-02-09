<?php
use Migrations\AbstractMigration;

class POCOR5694 extends AbstractMigration
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
        // security_functions
        $this->execute("UPDATE security_functions 
                        SET `_execute` = 'InstitutionLands.excel|InstitutionBuildings.excel|InstitutionFloors.excel|InstitutionRooms.excel' 
                        WHERE `id` = 1011 AND `name` = 'Infrastructure'" );
    }

    public function down()
    {
        // security_functions
        $this->execute("UPDATE security_functions 
                        SET `_execute` = NULL 
                        WHERE `id` = 1011 AND `name` = 'Infrastructure'");
    }

}
