<?php
use Migrations\AbstractMigration;

class POCOR7203 extends AbstractMigration
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
        $this->execute('ALTER TABLE `institution_positions` ADD COLUMN `is_homeroom` INT(1) NOT NULL DEFAULT 0 AFTER `shift_id`');
    }

    // Rollback
    public function down()
    {
        $this->execute('ALTER TABLE `institution_positions` DROP COLUMN `is_homeroom`');
    }
}
