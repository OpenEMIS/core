<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR8538 extends AbstractMigration
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
        // backup
        $this->execute('CREATE TABLE `z_8538_custom_modules` LIKE `custom_modules`');
        $this->execute('INSERT INTO `z_8538_custom_modules` SELECT * FROM `custom_modules`');
        
        $data = [
            'code' => 'Institution > Classes',
            'name' => 'Institution > Classes',
            'model' => 'Institution.InstitutionClasses',
            'visible' => 1,
            'parent_id' => 0,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s'),
            'modified' => null,
            'modified_user_id' => null
        ];
        $this->table('custom_modules')->insert($data)->save();
      
    }
    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `custom_modules`');
        $this->execute('RENAME TABLE `z_8538_custom_modules` TO `custom_modules`');
    }

}
