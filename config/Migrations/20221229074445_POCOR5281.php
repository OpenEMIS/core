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
        $this->execute("CREATE TABLE IF NOT EXISTS `period_shift_records` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `institution_shift_id` int(11),
            `period_id` int(11),
            PRIMARY KEY (`id`)
          )");
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `period_shift_records`');
    }
}
