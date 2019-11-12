<?php
use Migrations\AbstractMigration;

class POCOR5030 extends AbstractMigration
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
		// backup 
        $this->execute('CREATE TABLE `z_5030_institution_staff_releases` LIKE `institution_staff_releases`');
		
        $this->execute("ALTER TABLE `institution_staff_releases` CHANGE `new_institution_id` `new_institution_id` INT(11) NULL DEFAULT NULL COMMENT 'links to institutions.id'");
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `institution_staff_releases`');
        $this->execute('RENAME TABLE `z_5030_institution_staff_releases` TO `institution_staff_releases`');
    }
}
