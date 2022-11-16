<?php
use Migrations\AbstractMigration;

class POCOR6971 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_6971_institution_positions` LIKE `institution_positions`');
        $this->execute('INSERT INTO `zz_6971_institution_positions` SELECT * FROM `institution_positions`');
        // End

        $this->execute("ALTER TABLE `institution_positions` ADD `shift_id` INT NOT NULL DEFAULT 1 AFTER `assignee_id`");

        $this->execute("ALTER TABLE institution_positions DROP PRIMARY KEY");

        $this->execute("ALTER TABLE institution_positions ADD PRIMARY KEY(status_id,staff_position_title_id,staff_position_grade_id,institution_id,assignee_id,shift_id,)");
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `institution_positions`');
        $this->execute('RENAME TABLE `zz_6971_institution_positions` TO `institution_positions`');
    }
}
