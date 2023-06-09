<?php
use Migrations\AbstractMigration;

class POCOR7441 extends AbstractMigration
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
       $this->execute('ALTER TABLE `staff_behaviours` ADD COLUMN `action` TEXT NOT NULL AFTER `created`');
    }
    public function down()
    {
        $this->execute('ALTER TABLE `staff_behaviours` DROP COLUMN `action`');

    }
}
