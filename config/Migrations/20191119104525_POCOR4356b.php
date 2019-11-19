<?php

use Phinx\Migration\AbstractMigration;

class POCOR4356b extends AbstractMigration
{
	public function up()
    {
        $this->execute('UPADTE security_functions SET visible=0  WHERE category = "Timetable"');
    }
	
    // rollback
    public function down()
    {
		$this->execute('UPADTE security_functions SET visible=0  WHERE category = "Timetable"');
    }
}