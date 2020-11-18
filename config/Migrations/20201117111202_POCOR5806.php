<?php
use Migrations\AbstractMigration;

class POCOR5806 extends AbstractMigration
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
        $this->execute('ALTER TABLE `institution_expenditures` ADD COLUMN `institution_id` int AFTER `id`');
    }

    // rollback
    public function down()
    {
    }
}
