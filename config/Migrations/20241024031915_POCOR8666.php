<?php
use Migrations\AbstractMigration;

class POCOR8666 extends AbstractMigration
{
    public function up()
    {
        $this->execute("CREATE TABLE IF NOT EXISTS `scanned_attendances` (
            `code` int(11) NOT NULL AUTO_INCREMENT,
            `date` date NOT NULL,
            `time` time NOT NULL,
            `openemis_no` varchar(100) NOT NULL,
            `latitude` decimal(10, 8) NOT NULL,
            `longitude` decimal(11, 8) NOT NULL,
            `modified_user_id` int(11) DEFAULT NULL,
            `modified` datetime DEFAULT NULL,
            `created_user_id` int(11) DEFAULT NULL,
            `created` datetime DEFAULT NULL,
            PRIMARY KEY (`code`),
            FOREIGN KEY (`openemis_no`) REFERENCES `security_users` (`openemis_no`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
        );
    }


    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `scanned_attendances`');
        $this->execute('RENAME TABLE `z_8666_scanned_attendances` TO `scanned_attendances`');
    }
}
