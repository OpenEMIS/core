<?php
use Migrations\AbstractMigration;

class POCOR4039 extends AbstractMigration
{
    public function up()
    {
        // Event scheduler on db side will have to be turned on
        // $this->execute('SET GLOBAL event_scheduler = ON');
        $this->execute("
            DROP EVENT IF EXISTS `update_institution_statuses`;
            CREATE EVENT `update_institution_statuses` ON SCHEDULE EVERY 1 DAY STARTS CONCAT(CURRENT_DATE() + INTERVAL '1' DAY, ' 00:00:00')
            DO BEGIN
                UPDATE `institutions`
                SET `institution_status_id` = 2
                WHERE `date_closed` < CURRENT_DATE();
            END"
        );
    }

    public function down()
    {
        $this->execute('DROP EVENT IF EXISTS `update_institution_statuses`');
    }
}
