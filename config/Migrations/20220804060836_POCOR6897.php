<?php
use Migrations\AbstractMigration;

class POCOR6897 extends AbstractMigration
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
        /** backup */
        $this->execute('CREATE TABLE `zz_6897_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6897_security_functions` SELECT * FROM `security_functions`');
        /** updating existing record */
        $this->execute("UPDATE `security_functions` SET `_edit` = 'Distributions.edit' WHERE `category`='Meals' AND `module`='Institutions' AND `controller`='Institutions' AND `name` = 'Meals Distribution' ");
        $this->execute("UPDATE `security_functions` SET `_edit` = 'StudentMeals.edit' WHERE  `category`='Meals' AND `module`='Institutions' AND `controller`='Institutions' AND `name` = 'Meals Student' ");
    }

    /** rollback */ 
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6897_security_functions` TO `security_functions`');
    }
}
