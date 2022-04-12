<?php
use Migrations\AbstractMigration;
use Cake\Datasource\ConnectionManager;

class POCOR6608 extends AbstractMigration
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
        $this->execute('DROP TABLE IF EXISTS `zz_6608_meal_institution_programmes`');
        $this->execute('CREATE TABLE `zz_6608_meal_institution_programmes` LIKE `meal_institution_programmes`');
        $this->execute('INSERT INTO `zz_6608_meal_institution_programmes` SELECT * FROM `meal_institution_programmes`');

        // $this->execute('ALTER TABLE `meal_institution_programmes` ADD COLUMN `area_id` int(11) AFTER `institution_id`');
        $connection = ConnectionManager::get('default');

        $dbConfig = $connection->config();
        $dbname = $dbConfig['database']; 
        $CheckAreaIDExist = "SELECT COUNT(*)
         FROM information_schema.columns 
         WHERE table_name   = 'meal_institution_programmes'
         AND table_schema = '$dbname'
         AND column_name  = 'area_id'";
         $tableData = $this->fetchAll($CheckAreaIDExist);
         if($tableData[0][0] == 0){
            $this->execute('ALTER TABLE `meal_institution_programmes` ADD COLUMN `area_id` int(11) AFTER `institution_id` ');
            $this->execute("ALTER TABLE `meal_institution_programmes` CHANGE `area_id` `area_id` INT(11) NULL DEFAULT NULL COMMENT 'Links to areas.id' ");
         }
        // sleep(30);
        // $this->execute("ALTER TABLE `meal_institution_programmes` CHANGE `area_id` `area_id` INT(11) NULL DEFAULT NULL COMMENT 'Links to areas.id' ");
        // $this->execute("CREATE PROCEDURE ADD_Comment() BEGIN DECLARE _count INT; SET _count = (  SELECT COUNT(*)  FROM INFORMATION_SCHEMA.COLUMNS WHERE   TABLE_NAME = 'meal_institution_programmes' AND COLUMN_NAME = 'area_id'); IF _count = 1 THEN ALTER TABLE `meal_institution_programmes` CHANGE `area_id` `area_id` INT(11) NULL DEFAULT NULL COMMENT 'Links to areas.id' ; END IF; END ; ");
    }

    // rollback
    public function down()
    {
       // meal_institution_programmes
       $this->execute('DROP TABLE IF EXISTS `meal_institution_programmes`');
       $this->execute('RENAME TABLE `zz_6608_meal_institution_programmes` TO `meal_institution_programmes`');
    }
}
