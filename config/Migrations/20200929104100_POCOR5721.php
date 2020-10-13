<?php
use Migrations\AbstractMigration;

class POCOR5721 extends AbstractMigration
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
         // openemis_temps
		$this->execute('CREATE TABLE `zz_5721_openemis_temps` LIKE `openemis_temps`');
        $this->execute('INSERT INTO `zz_5721_openemis_temps` SELECT * FROM `openemis_temps`');
		
		$this->execute('TRUNCATE openemis_temps');
    }

    // rollback
    public function down()
    {
        //openemis_temps
        $this->execute('DROP TABLE IF EXISTS `openemis_temps`');
        $this->execute('RENAME TABLE `zz_5721_openemis_temps` TO `openemis_temps`');
    }
}
