<?php
use Migrations\AbstractMigration;

class POCOR8666 extends AbstractMigration
{
    public function up()
    {
        $this->execute("CREATE TABLE IF NOT EXISTS `institution_scanned` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `openemis_no` varchar(100) NOT NULL,
            `datetime` datetime NOT NULL,
            `latitude` decimal(10, 8)  NULL,
            `longitude` decimal(11, 8)  NULL,
            `access` varchar(100)  NULL,
            `location` varchar(100)  NULL,
            `modified_user_id` int(11) DEFAULT NULL,
            `modified` datetime DEFAULT NULL,
            `created_user_id` int(11) DEFAULT NULL,
            `created` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`openemis_no`) REFERENCES `security_users` (`openemis_no`),
            FOREIGN KEY (`created_user_id`) REFERENCES `security_users` (`id`),
            FOREIGN KEY (`modified_user_id`) REFERENCES `security_users` (`id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
        );
    }


    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `institution_scanned`');
        $this->execute('RENAME TABLE `z_8666_institution_scanned` TO `institution_scanned`');
    }
}
