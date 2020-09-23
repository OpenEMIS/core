<?php
use Migrations\AbstractMigration;

class POCOR5461 extends AbstractMigration
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
        // Backup locale_contents table
        $this->execute('CREATE TABLE `security_group_classes` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `security_group_id` int(11) NOT NULL,
              `security_user_id` int(11) NOT NULL,
              `security_role_id` int(11) NOT NULL,
              `institution_class_id` int(11) NOT NULL,
              `institution_subject_id` int(11) NOT NULL,
              PRIMARY KEY (`id`)
            )');
        $this->execute("INSERT INTO `security_group_classes`
        (security_group_id, security_user_id, security_role_id,institution_class_id)
        SELECT
        institution_classes.institution_id,institution_classes.staff_id,security_group_users.security_role_id,institution_classes.id
        FROM 
        `institution_classes` 
        LEFT JOIN
        security_group_users
        ON
        institution_classes.staff_id = security_group_users.security_user_id
        WHERE institution_classes.staff_id != 0");
        // End
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_group_classes`');
    }
}
