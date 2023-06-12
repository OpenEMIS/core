<?php
use Migrations\AbstractMigration;

class POCOR5191 extends AbstractMigration
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
        $this->execute("CREATE TABLE `student_profile_security_roles` (
            `id` int NOT NULL AUTO_INCREMENT,
            `security_role_id` int NOT NULL,
            `student_profile_template_id` int NOT NULL,
            `created` datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");
    }
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `student_profile_security_roles`');
        $this->execute('RENAME TABLE `zz_5191_student_profile_security_roles` TO `student_profile_security_roles`');
    }
}
