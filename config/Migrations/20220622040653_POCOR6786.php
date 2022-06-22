<?php
use Migrations\AbstractMigration;

class POCOR6786 extends AbstractMigration
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
        /**Backup api_securities table*/
        $this->execute('CREATE TABLE `z_6786_api_securities` LIKE `api_securities`');
        $this->execute('INSERT INTO `z_6786_api_securities` SELECT * FROM `api_securities`');
        
        /**Inserting record*/
        $this->insert('api_securities', [
            'name' => 'Assessment',
            'model' => 'Assessment.Assessments'
        ]);
    }

    /**rollback*/
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `api_securities`');
        $this->execute('RENAME TABLE `z_6786_api_securities` TO `api_securities`');
    }
}
