<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR8047 extends AbstractMigration
{
    public function up()
    {
        //backup
        $this->execute('CREATE TABLE `z_8047_institution_trips` LIKE `institution_trips`');
        $this->execute('INSERT INTO `z_8047_institution_trips` SELECT * FROM `institution_trips`');

        //Change repeat column into trip_repeat
        $this->execute("ALTER TABLE `institution_trips` CHANGE `repeat` `trip_repeat` INT(11) NOT NULL DEFAULT '0'");
    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `institution_trips`');
        $this->execute('RENAME TABLE `z_8047_institution_trips` TO `institution_trips`');
    }

}
