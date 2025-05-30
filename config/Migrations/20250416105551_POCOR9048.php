<?php
declare(strict_types=1);

use Migrations\AbstractMigration;
use Cake\Utility\Text;

class POCOR9048 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function up(): void
    {
        $this->execute('CREATE TABLE `z_9048_labels` LIKE `labels`');
        $this->execute('INSERT INTO `z_9048_labels` SELECT * FROM `labels`');

        $uuid = Text::uuid(); // generates a random UUID v4
        $this->execute("INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `code`, `name`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES
	('$uuid', 'Programmes', 'registration_number', 'Institution-> Students-> Academic-> Programme', 'Registration Number', NULL, NULL, 1, NULL, NULL, 1,  NOW());");
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `labels`');
        $this->execute('RENAME TABLE `z_9048_labels` TO `labels`');
    }
}
