<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR9769 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function up()
	{
			// Backup table
        $this->execute('CREATE TABLE `z_9769_config_items` LIKE `config_items`');
        $this->execute('INSERT INTO `z_9769_config_items` SELECT * FROM `config_items`');

        // Update existing record
        $this->execute(
            'UPDATE `config_items`
             SET `value` = "updates@openemis.org"
             WHERE `code` = "version_support_emails"'
        );
	}

	public function down()
	{
		// Restore only the modified record
        $this->execute(
            'UPDATE `config_items` c
             JOIN `z_9769_config_items` z
               ON c.id = z.id
             SET c.value = z.value
             WHERE c.code = "version_support_emails"'
        );

        // Drop backup table
        $this->execute('DROP TABLE IF EXISTS `z_9769_config_items`');
	}
}
