<?php

use Phinx\Migration\AbstractMigration;

class POCOR6163 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function up()
    {
        $this->execute('CREATE TABLE `zz_6163_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6163_security_functions` SELECT * FROM `security_functions`');

		$this->execute("UPDATE `security_functions` SET `_execute` = 'Expenditure.excel' WHERE `category`='Finance' AND `name` = 'Expenditure' AND controller='Institutions' AND module = 'Institutions'");
    }

    public function down() {
		$this->execute('DROP TABLE IF EXISTS `security_functions`');
		$this->execute('RENAME TABLE `zz_6163_security_functions` TO `security_functions`');
	}
}
