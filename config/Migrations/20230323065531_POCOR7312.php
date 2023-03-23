<?php
use Migrations\AbstractMigration;

class POCOR7312 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    // commit
    public function up()
    {
        // Backup table
        // $this->execute('CREATE TABLE `zz_7312_api_credentials` LIKE `api_credentials`');
        // $this->execute('INSERT INTO `zz_7312_api_credentials` SELECT * FROM `api_credentials`');

        $this->execute("ALTER TABLE `api_credentials` CHANGE `client_id` `client_id` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;");
        $this->execute("ALTER TABLE `api_credentials` CHANGE `public_key` `public_key` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;");
    }

    // rollback
    public function down()
    {
        // Restore table
        $this->execute('DROP TABLE IF EXISTS `api_credentials`');
        $this->execute('RENAME TABLE `zz_7312_api_credentials` TO `api_credentials`');
    }
}
