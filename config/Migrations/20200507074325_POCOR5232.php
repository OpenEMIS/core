<?php

use Phinx\Migration\AbstractMigration;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;

class POCOR5232 extends AbstractMigration
{
    public function up()
    {        
        // security_functions
        $this->execute('CREATE TABLE `z_5232_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_5232_security_functions` SELECT * FROM `security_functions`');
		
        $this->execute("UPDATE `security_functions` SET `_view` = 'ScheduleTimetableOverview.index|ScheduleTimetableOverview.view|ScheduleTimetableOverview', `_edit` = 'ScheduleTimetableOverview.edit', `_add` = 'ScheduleTimetableOverview.add' WHERE `category`='Schedules' AND `_view` = 'Institutions.ScheduleTimetableOverview' ");
        
		$this->execute("UPDATE `security_functions` SET `_view` = 'ScheduleIntervals.index|ScheduleIntervals.view|ScheduleIntervals', `_edit` = 'ScheduleIntervals.edit', `_add` = 'ScheduleIntervals.add' WHERE `category`='Schedules' AND `_view` = 'Institutions.ScheduleIntervals' ");
		
		$this->execute("UPDATE `security_functions` SET `_view` = 'ScheduleTerms.index|ScheduleTerms.view|ScheduleTerms', `_edit` = 'ScheduleTerms.edit', `_add` = 'ScheduleTerms.add' WHERE `category`='Schedules' AND `_view` = 'Institutions.ScheduleTerms' ");
    }

    public function down()
    {
		//security_functions
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_5232_security_functions` TO `security_functions`');		
    }
}
