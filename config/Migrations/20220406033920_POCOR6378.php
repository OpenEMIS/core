<?php
use Migrations\AbstractMigration;

class POCOR6378 extends AbstractMigration
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
        /** START: institutions table changes */
        $this->execute('DROP TABLE IF EXISTS `zz_6378_institutions`');
        $this->execute('CREATE TABLE `zz_6378_institutions` LIKE `institutions`');
        $this->execute('INSERT INTO `zz_6378_institutions` SELECT * FROM `institutions`');

        $this->execute("UPDATE `institutions` SET `area_administrative_id` = (SELECT id FROM `area_administratives` WHERE `is_main_country` = 1) WHERE `institutions`.`area_administrative_id` = NULL");
        
        $this->execute("ALTER TABLE `institutions` CHANGE `area_administrative_id` `area_administrative_id` INT(11) NOT NULL");
        /** END: institutions table changes */
    }

    //rollback
    public function down()
    {
        /** START: institutions table changes */
        $this->execute('DROP TABLE IF EXISTS `institutions`');
        $this->execute('RENAME TABLE `zz_6378_institutions` TO `institutions`');
        /** END: institutions table changes */
    }
}