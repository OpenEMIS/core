<?php
use Migrations\AbstractMigration;

class POCOR6021 extends AbstractMigration
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
        //deleting record if already exist
        $this->execute("DELETE FROM locale_contents WHERE en = 'No Scheduled Class'");
        /*inserting data*/
        $this->execute("INSERT INTO `locale_contents` (`en`, `created_user_id`, `created`) VALUES ('No Scheduled Class', '1', NOW())");
    }

    //rollback
    public function down()
    {
        $this->execute("DELETE FROM locale_contents WHERE en = 'No Scheduled Class'");
    }
}
