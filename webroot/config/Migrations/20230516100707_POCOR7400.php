<?php
use Migrations\AbstractMigration;

class POCOR7400 extends AbstractMigration
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
        $this->execute('CREATE TABLE IF NOT EXISTS `assessment_period_excluded_security_roles` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `assessment_period_id` int(11) ,
            `security_role_id` int(11),
            PRIMARY KEY (`id`),
            FOREIGN KEY (`assessment_period_id`) REFERENCES `assessment_periods` (`id`),
            FOREIGN KEY (`security_role_id`) REFERENCES `security_roles` (`id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
          ');

        $this->execute('CREATE TABLE IF NOT EXISTS `report_card_excluded_security_roles` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `report_card_id` int(11) ,
            `security_role_id` int(11),
            PRIMARY KEY (`id`),
            FOREIGN KEY (`report_card_id`) REFERENCES `report_cards` (`id`),
            FOREIGN KEY (`security_role_id`) REFERENCES `security_roles` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ');
    }
    
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `assessment_period_excluded_security_roles`');
        $this->execute('DROP TABLE IF EXISTS `report_card_excluded_security_roles`');
    }
}



