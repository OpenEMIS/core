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
        $this->execute('CREATE TABLE `zz_6786_api_securities` LIKE `api_securities`');
        $this->execute('INSERT INTO `zz_6786_api_securities` SELECT * FROM `api_securities`');
        $row = $this->fetchRow("SELECT `id` FROM `api_securities` ORDER BY `id` DESC LIMIT 1");
        $id = $row['id'];
        /**Inserting record*/
        $this->insert('api_securities', [
            'id' => $id + 1,
            'name' => 'Assessment',
            'model' => 'Assessment.Assessments',
            'index' => 1,
            'view' => 1,
            'add' => 1,
            'edit' => 1,
            'delete' => 0,
            'execute' => 0
        ]);
    }

    /**rollback*/
    public function down()
    {
        //$this->execute('DROP TABLE IF EXISTS `api_securities`');
        //$this->execute('RENAME TABLE `zz_6786_api_securities` TO `api_securities`');
    }
}
