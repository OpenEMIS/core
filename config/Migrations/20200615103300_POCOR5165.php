<?php
use Migrations\AbstractMigration;

class POCOR5165 extends AbstractMigration
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
        $this->execute('CREATE TABLE `z_5165_locale_contents` LIKE `locale_contents`');
        $this->execute('INSERT INTO `z_5165_locale_contents` SELECT * FROM `locale_contents`');
        // End
		
		$localeContent = [
            [
                'en' => 'Staff Name',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
			[
                'en' => 'Training Courses Report',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('locale_contents', $localeContent);
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `locale_contents`');
        $this->execute('RENAME TABLE `z_5165_locale_contents` TO `locale_contents`');
    }
}
