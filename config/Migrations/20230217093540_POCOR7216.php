<?php
use Migrations\AbstractMigration;

class POCOR7216 extends AbstractMigration
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
        $this->execute('ALTER TABLE `institution_staff_position_profiles` MODIFY COLUMN `end_date` DATE NULL');
    }

    public function down()
    {
        $this->execute('ALTER TABLE `institution_staff_position_profiles` MODIFY COLUMN `end_date` DATE NOT NULL');
    }
}
