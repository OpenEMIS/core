<?php
use Migrations\AbstractMigration;

class POCOR5000 extends AbstractMigration
{
    public function up() 
    {
        $this->execute('ALTER TABLE examination_centres CHANGE COLUMN `fax` `fax` VARCHAR(30) NULL');
        $this->execute('ALTER TABLE examination_centres CHANGE COLUMN `telephone` `telephone` VARCHAR(30) NOT NULL DEFAULT 0');
    }



    public function down() 
    {
        $this->execute('ALTER TABLE examination_centres CHANGE COLUMN `fax` `fax` VARCHAR(30) NOT NULL');
        $this->execute('ALTER TABLE examination_centres CHANGE COLUMN `telephone` `telephone` VARCHAR(30) NULL');
    }

}