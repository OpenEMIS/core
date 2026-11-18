<?php
use Migrations\AbstractMigration;

class POCOR9924 extends AbstractMigration
{
    // POCOR-9924: Administration > Security > Roles > Permissions - introduce an Execute function on
    // Institutions > Students > "Attendance" to control the Export button on
    // Institutions > Attendance > Students (StudentAttendances). The export request is authorised
    // against StudentAttendances.excel (SecurityAuthorize, CAv4 pass[0]), which no function granted.
    public function up()
    {
        $this->execute('CREATE TABLE IF NOT EXISTS `zz_9924_security_functions` LIKE `security_functions`');
        $this->execute("
            INSERT IGNORE INTO `zz_9924_security_functions`
            SELECT * FROM `security_functions`
            WHERE `name` = 'Attendance'
              AND `controller` = 'Institutions'
              AND `module` = 'Institutions'
              AND `category` = 'Students'
        ");

        // Guarded so a site patched by hand is not given a duplicate segment.
        $this->execute("
            UPDATE `security_functions`
            SET `_execute` = CONCAT_WS('|', NULLIF(`_execute`, ''), 'StudentAttendances.excel'),
                `modified_user_id` = 1,
                `modified` = NOW()
            WHERE `name` = 'Attendance'
              AND `controller` = 'Institutions'
              AND `module` = 'Institutions'
              AND `category` = 'Students'
              AND (`_execute` IS NULL OR `_execute` NOT LIKE '%StudentAttendances.excel%')
        ");
    }

    public function down()
    {
        $this->execute("
            UPDATE `security_functions` AS `sf`
            INNER JOIN `zz_9924_security_functions` AS `bk` ON `bk`.`id` = `sf`.`id`
            SET `sf`.`_execute` = `bk`.`_execute`,
                `sf`.`modified_user_id` = `bk`.`modified_user_id`,
                `sf`.`modified` = `bk`.`modified`
        ");

        $this->execute('DROP TABLE IF EXISTS `zz_9924_security_functions`');
    }
}
