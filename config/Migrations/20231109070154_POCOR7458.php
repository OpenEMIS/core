<?php
use Migrations\AbstractMigration;

class POCOR7458 extends AbstractMigration
{
    public function up()
    {
        $this->execute("CREATE TABLE `messaging` (
                            `id` int(11)  NOT NULL AUTO_INCREMENT,
                            `message` varchar(255) NOT NULL,
                            `subject` varchar(255) NOT NULL,
                            `recipient_level_id` int(11) NOT NULL,
                            `recipient_group_id` varchar(11) NOT NULL,
                            `academic_period_id` int(11) NOT NULL,
                            `institution_id` int(11) NOT NULL,
                            `status` int(11) NOT NULL DEFAULT 0 COMMENT '0->Draft 1->Send',
                            `created_user_id` int(11) NOT NULL,
                            `created` datetime NOT NULL,
                            `modified_user_id` int(11)  NULL,
                            `modified` datetime NULL,
                            PRIMARY KEY (`id`),
                            FOREIGN KEY (`academic_period_id`) REFERENCES `academic_periods` (`id`),
                            FOREIGN KEY (`institution_id`) REFERENCES `institutions` (`id`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        
        $this->execute("CREATE TABLE `message_recipients` (
                            `id` int(11) NOT NULL AUTO_INCREMENT,
                            `message_id` int(11) NOT NULL,
                            `recipient_id` int(11) NOT NULL,
                            PRIMARY KEY (`id`),
                            FOREIGN KEY (`message_id`) REFERENCES `messaging` (`id`),
                            FOREIGN KEY (`recipient_id`) REFERENCES `security_users` (`id`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $this->execute("CREATE TABLE `messaging_security_roles` (
                            `id` int(11) NOT NULL AUTO_INCREMENT,
                            `message_id` int(11) NOT NULL,
                            `security_role_id` int(11) NOT NULL,
                            PRIMARY KEY (`id`),
                            FOREIGN KEY (`message_id`) REFERENCES `messaging` (`id`),
                            FOREIGN KEY (`security_role_id`) REFERENCES `security_roles` (`id`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $this->execute('CREATE TABLE `z_7458_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_7458_security_functions` SELECT * FROM `security_functions`');
        $row = $this->fetchRow("SELECT * FROM `security_functions` WHERE `module` = 'Institutions' AND `name` = 'Institution' AND `category` = 'General'");
        $parentId = $row['id'];
        $row = $this->fetchRow("SELECT * FROM `security_functions` WHERE `module` = 'Institutions' AND `name` = 'Institution' AND `category` = 'Cases'");
        $orderId = $row['order'];
        $this->insert('security_functions', [
            'name' => 'Messaging',
            'controller' => 'Institutions',
            'module' => 'Institutions',
            'category' => 'Messaging',
            'parent_id' => $parentId,
            '_view' => 'Messaging.index|Messaging.view',
            '_edit' => 'Messaging.edit',
            '_add' => 'Messaging.add',
            '_delete' => 'Messaging.remove',
            '_execute'=>null,
            'order' => $orderId+1,
            'visible' => 1,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);
        $this->insert('security_functions', [
            'name' => 'Message Recipients',
            'controller' => 'Institutions',
            'module' => 'Institutions',
            'category' => 'Messaging',
            'parent_id' => $parentId,
            '_view' => 'MessageRecipients.index',
            '_edit' => null,
            '_add' => null,
            '_delete' => null,
            '_execute' => null,
            'order' => $orderId+2,
            'visible' => 1,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);
    }
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `messaging_security_roles`');
        $this->execute('DROP TABLE IF EXISTS `message_recipients`');
        $this->execute('DROP TABLE IF EXISTS `messaging`');
        $this->execute('DROP TABLE IF EXISTS security_functions');
        $this->execute('RENAME TABLE `z_7458_security_functions` TO `security_functions`');
    }
}
