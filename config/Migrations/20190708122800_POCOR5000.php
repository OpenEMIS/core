<?php
use Migrations\AbstractMigration;

class POCOR5000 extends AbstractMigration
{
    public function up()
    {
		$this->execute('ALTER TABLE examination_centres CHANGE COLUMN `fax` VARCHAR(30) NULL');
	}
}