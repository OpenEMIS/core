<?php
use Migrations\AbstractMigration;

class POCOR9366 extends AbstractMigration
{
    public function up()
    {
        $this->execute('DROP EVENT IF EXISTS `delete_openemis_temps_at_midnight`');
        $this->execute(
            "CREATE EVENT `delete_security_users_openemis_no`
             ON SCHEDULE EVERY 1 DAY
             STARTS '2019-10-12 00:00:00'
             ON COMPLETION NOT PRESERVE
             ENABLE
             DO
             DELETE FROM security_users_openemis_no
             WHERE created < DATE_SUB(NOW(), INTERVAL 1 DAY)"
        );
    }

    public function down()
    {

    }
}
