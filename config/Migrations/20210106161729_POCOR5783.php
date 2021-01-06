<?php
use Migrations\AbstractMigration;

class POCOR5783 extends AbstractMigration
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
        $this->execute('CREATE TABLE `z_5783_institution_committees` LIKE `institution_committees`');
        $table = $this->table('institution_committees');
        $table->removeColumn('meeting_date');
        $table->removeColumn('start_time');
        $table->removeColumn('end_time')
              ->save();

        //meeting_table
        $this->execute("CREATE TABLE `institution_committee_meeting` ( `id` int(11) NOT NULL AUTO_INCREMENT,
        `meeting_date` varchar(250) NOT NULL,
        `start_time` varchar(255) NOT NULL,
        `end_time` varchar(255) NOT NULL,
        `comment` varchar(255) NOT NULL,
        `institution_committee_id` int(11) NOT NULL COMMENT 'links to institution_committees.id',
        `modified_user_id` int(11) DEFAULT NULL,
        `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`))");
    }

     // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `institution_committees`');
        $this->execute('RENAME TABLE `z_5783_institution_committees` TO `institution_committees`');
        $this->execute('DROP TABLE IF EXISTS `institution_committee_meeting`');
    }
}
