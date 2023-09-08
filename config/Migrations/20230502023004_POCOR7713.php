<?php
use Migrations\AbstractMigration;

class POCOR7713 extends AbstractMigration
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
        // Backup table
        $this->execute('DROP TABLE IF EXISTS `zz_7713_phinxlog`');
	$this->execute('CREATE TABLE `zz_7713_phinxlog` LIKE `phinxlog`');
	$this->execute('INSERT INTO `zz_7713_phinxlog` SELECT * FROM `phinxlog`');

	// Update record for 20230515023003_POCOR7395.php
	$this->execute('UPDATE `phinxlog` SET `version` = "20230502023003" WHERE `migration_name` LIKE "POCOR7395"');
    }

    public function down()
    {

	//Restore table
	$this->execute('DROP TABLE IF EXISTS `phinxlog`');
	$this->execute('RENAME TABLE `zz_7713_phinxlog` to `phinxlog`');
     }
}
