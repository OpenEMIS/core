<?php
use Migrations\AbstractMigration;

class POCOR6160 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function up() {
		
		$this->execute('CREATE TABLE `zz_6160_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6160_security_functions` SELECT * FROM `security_functions`');
		
		$this->execute("UPDATE `security_functions` SET `_execute` = 'BankAccounts.excel' WHERE `category`='Finance' AND `name` = 'Bank Accounts' AND controller='Institutions' AND module = 'Institutions'");

	}
	
	public function down() {
		$this->execute('DROP TABLE IF EXISTS `security_functions`');
		$this->execute('RENAME TABLE `zz_6160_security_functions` TO `security_functions`');
	}
}
