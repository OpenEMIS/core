<?php
use Migrations\AbstractMigration;

class POCOR6353 extends AbstractMigration
{
    /**
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
	 * This class is used for Update personal Comment in database
     * @author Akshay patodi <akshay.patodi@mail.valuecoders.com>
	 * @ticket POCOR-6353
     */
     public function up()
    {
		
		 // security_functions
    	$this->execute('DROP TABLE IF EXISTS `zz_6353_security_functions`');
        $this->execute('CREATE TABLE `zz_6353_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6353_security_functions` SELECT * FROM `security_functions`');
		$this->execute('UPDATE `security_functions` SET `_view` = "Comments.index|Comments.view", `_edit` = "Comments.edit", `_add` = "Comments.add", `_delete` = "Comments.remove", `_execute` = "Comments.excel" WHERE `id` = "9038"');
		
    }
	
	 public function down()
    {   
	    // security_functions
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6353_security_functions` TO `security_functions`');
		
    }
}
