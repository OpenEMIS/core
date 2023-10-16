<?php
use Migrations\AbstractMigration;

class POCOR5281 extends AbstractMigration
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
        $this->execute("CREATE TABLE IF NOT EXISTS `institution_shift_periods` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `institution_shift_period_id` int(11),
            `period_id` int(11),
            PRIMARY KEY (`id`),
            FOREIGN KEY (`institution_shift_period_id`) REFERENCES `institution_shifts` (`id`),
            FOREIGN KEY (`period_id`) REFERENCES `student_attendance_per_day_periods` (`id`)
          )ENGINE=InnoDB DEFAULT CHARSET=utf8"
          );
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `institution_shift_periods`');
    }
}
