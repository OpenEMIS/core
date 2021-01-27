<?php
use Migrations\AbstractMigration;

class POCOR5788 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
	public function up()
	// Backup table
	{
		$this->execute('CREATE TABLE `zz_5788_security_functions` LIKE `security_functions`');
		$this->execute('INSERT INTO `zz_5788_security_functions` SELECT * FROM `security_functions`');
		$this->execute('UPDATE security_functions SET _add = "StudentAttendances.add" WHERE `name` = "Attendance" AND `controller` = "Institutions" AND `module` = "Institutions" AND `category` = "Students"');
	} 
 
    public function down()
	// rollback
	{
		$this->execute('DROP TABLE IF EXISTS `security_functions`');
		$this->execute('RENAME TABLE `zz_5788_security_functions` TO `security_functions`');
	}
}
