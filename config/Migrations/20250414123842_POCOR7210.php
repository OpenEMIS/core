<?php
use Migrations\AbstractMigration;

class POCOR7210 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_7210_notices` LIKE `notices`');
        $this->execute('INSERT INTO `zz_7210_notices` SELECT * FROM `notices`');
        //alter
        $this->execute("ALTER TABLE `notices` ADD COLUMN `status` INT(11) NOT NULL COMMENT '1 -> Enable, 0 -> Disable'");

        $this->execute('ALTER TABLE `notices` ADD COLUMN `subject` VARCHAR(255) NOT NULL AFTER `id`');
        $this->execute('
            CREATE TABLE IF NOT EXISTS `notice_roles` (
                `id` CHAR(64) NOT NULL,
                `security_role_id` INT(11) NOT NULL,
                `notice_id` INT(11) NOT NULL,
                PRIMARY KEY (`id`),
                CONSTRAINT `fk_notice_roles_security_role` FOREIGN KEY (`security_role_id`) REFERENCES `security_roles` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_notice_roles_notice` FOREIGN KEY (`notice_id`) REFERENCES `notices` (`id`) ON DELETE CASCADE
            )
        ');
        
    }

    // rollback
    public function down()
    {
        // Restore table
        $this->execute('DROP TABLE IF EXISTS `notices`');
        $this->execute('RENAME TABLE `zz_7210_notices` TO `notices`');
        $this->execute('DROP TABLE IF EXISTS `notice_roles`');
    }
}