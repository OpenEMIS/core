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

        $this->execute('CREATE TABLE `zz_7210_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_7210_security_functions` SELECT * FROM `security_functions`');
        //alter
        $this->execute("ALTER TABLE `notices` ADD COLUMN `status` INT(11) NOT NULL COMMENT '1 → Enable, 0 → Disable' AFTER `message`");

        $this->execute('ALTER TABLE `notices` ADD COLUMN `subject` VARCHAR(255) NOT NULL AFTER `id`');
        $this->execute('
            CREATE TABLE IF NOT EXISTS `notice_roles` (
                `id` CHAR(36) NOT NULL,
                `security_role_id` INT(11) NOT NULL,
                `notice_id` INT(11) NOT NULL,
                PRIMARY KEY (`id`),
                CONSTRAINT `fk_notice_roles_security_role` FOREIGN KEY (`security_role_id`) REFERENCES `security_roles` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_notice_roles_notice` FOREIGN KEY (`notice_id`) REFERENCES `notices` (`id`) ON DELETE CASCADE
            )
        ');

        $this->execute('
            CREATE TABLE IF NOT EXISTS `security_user_notices` (
                `id` CHAR(36) NOT NULL,
                `security_user_id` INT(11) NOT NULL,
                `notice_id` INT(11) NOT NULL,
                PRIMARY KEY (`id`),
                CONSTRAINT `fk_notice_roles_security_user` FOREIGN KEY (`security_user_id`) REFERENCES `security_users` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_notice_user` FOREIGN KEY (`notice_id`) REFERENCES `notices` (`id`) ON DELETE CASCADE
            )
        ');

        $row = $this->fetchRow("SELECT MAX(`order`) FROM `security_functions` WHERE `module` = 'Administration' AND `category` = 'Communications'");
        $parent_id = $this->fetchRow("SELECT MAX(`parent_id`) FROM `security_functions` WHERE `module` = 'Administration' AND `category` = 'Communications'");
        $parentId = $parent_id[0];
        $order = $row[0] + 1;
        $record = [
            [
                'name' => 'Notice', 'controller' => 'Alerts', 'module' => 'Administration', 'category' => 'Communications', 'parent_id' => $parentId,'_view' => 'Notices.index|Notices.view', '_edit' => 'Notices.edit', '_add' => 'Notices.add', '_delete' => 'Notices.remove', '_execute' => NULL, 'order' => $order, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ]
        ];
        $this->table('security_functions')->insert($record)->save();

        $row = $this->fetchRow("SELECT MAX(`order`) FROM `security_functions` WHERE `module` = 'Administration' AND `category` = 'Communications'");
        $parent_id = $this->fetchRow("SELECT MAX(`parent_id`) FROM `security_functions` WHERE `module` = 'Administration' AND `category` = 'Communications'");
        $parentId = $parent_id[0];
        $order = $row[0] + 1;
        $record = [
            [
                'name' => 'Notice Message', 'controller' => 'Systems', 'module' => 'Administration', 'category' => 'Communications', 'parent_id' => $parentId,'_view' => 'SystemNotices.index|SystemNotices.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => $order, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ]
        ];
        $this->table('security_functions')->insert($record)->save();
        
    }

    // rollback
    public function down()
    {
        $this->execute('ALTER TABLE `notices` DROP COLUMN `status`');

        // Remove foreign keys before dropping tables
        $this->execute('ALTER TABLE `notice_roles` DROP FOREIGN KEY `fk_notice_roles_security_role`');
        $this->execute('ALTER TABLE `notice_roles` DROP FOREIGN KEY `fk_notice_roles_notice`');
        $this->execute('ALTER TABLE `security_user_notices` DROP FOREIGN KEY `fk_notice_roles_security_user`');
        $this->execute('ALTER TABLE `security_user_notices` DROP FOREIGN KEY `fk_notice_user`');

        // Drop newly created tables
        $this->execute('DROP TABLE IF EXISTS `notice_roles`');
        $this->execute('DROP TABLE IF EXISTS `security_user_notices`');

        // Restore original tables
        $this->execute('DROP TABLE IF EXISTS `notices`');
        $this->execute('RENAME TABLE `zz_7210_notices` TO `notices`');
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_7210_security_functions` TO `security_functions`');
    }
}