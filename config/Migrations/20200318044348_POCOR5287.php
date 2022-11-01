<?php
use Migrations\AbstractMigration;

class POCOR5287 extends AbstractMigration
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
        $this->execute('CREATE TABLE `z_5287_locale_contents` LIKE `locale_contents`');
        $this->execute('INSERT INTO `z_5287_locale_contents` SELECT * FROM `locale_contents`');
		
        $table = $this->table('locale_contents');
        $data = [
            [
                'en' => 'Create New Student',
                'created_user_id' => 1,
                'created' => '2020-03-18 16:47:49'
            ]
        ];
        $table->insert($data)->save();
    }
    
    public function down()
    {
        // security_functions
        $this->execute('DROP TABLE IF EXISTS `locale_contents`');
        $this->execute('RENAME TABLE `z_5287_locale_contents` TO `locale_contents`');
        
    }
}
