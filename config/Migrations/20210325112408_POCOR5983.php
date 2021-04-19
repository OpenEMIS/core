<?php
use Phinx\Migration\AbstractMigration;

class POCOR5983 extends AbstractMigration
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
        $this->execute('CREATE TABLE `z_5983_locale_contents` LIKE `locale_contents`');
        $this->execute('INSERT INTO `z_5983_locale_contents` SELECT * FROM `locale_contents`');
        // End

        // For locale_contents table
        $localeContent = [
            [
                'en' => 'You are about to update the status of the Institution. This action will affect the availability of the Institution in the system.',
                'created_user_id' => 1,
                'created' => '2021-03-25 17:09:49'
            ]
        ];
        $this->insert('locale_contents', $localeContent);
        // End
    }

    public function down()
    {
       $this->execute("DELETE FROM `locale_contents` WHERE `en` = 'You are about to update the status of the Institution. This action will affect the availability of the Institution in the system.'");
    }
}
