<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR8817 extends AbstractMigration
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
            $this->execute('SET FOREIGN_KEY_CHECKS = 0');
            $this->execute('CREATE TABLE IF NOT EXISTS `z_8817_contact_options` LIKE `contact_options`');
            $this->execute('INSERT IGNORE INTO `z_8817_contact_options` SELECT * FROM `contact_options`');

            $this->execute(' UPDATE `contact_options`
                SET code = CASE 
                    WHEN name = "Mobile" THEN "MOB"
                    WHEN name = "Phone" THEN "PHO"
                    WHEN name = "Fax" THEN "FAX"
                    WHEN name = "Email" THEN "EMA"
                    WHEN name = "Emergency" THEN "EMG"
                    WHEN name = "Facebook" THEN "FBK"
                    WHEN name = "Telegram" THEN "TGM"
                    WHEN name = "Whatsapp" THEN "WHA"
                    WHEN name = "Other" THEN "OTH"
                    ELSE code
                END
        ');
            $this->execute('SET FOREIGN_KEY_CHECKS = 1');
        }

        public function down()
        {
            $this->execute('SET FOREIGN_KEY_CHECKS = 0');
            $this->execute('DROP TABLE IF EXISTS `contact_options`');
            $this->execute('RENAME TABLE `z_8817_contact_options` TO `contact_options`');
            $this->execute('SET FOREIGN_KEY_CHECKS = 1');
        }
}
