<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR8627 extends AbstractMigration
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
        $this->execute('CREATE TABLE `z_8627_field_types` LIKE `field_types`');
        $this->execute('INSERT INTO `z_8627_field_types` SELECT * FROM `field_types`');
        $fieldData = [
            'code' => 'TEXTAREA',
            'name' => 'Note'
        ];

        $this->table('field_types')->insert($fieldData)->save();
       
    }

    public function down(): void
    {
        $this->execute('DROP TABLE IF EXISTS `field_types`');
        $this->execute('RENAME TABLE `z_8627_field_types` TO `field_types`');
    }
}
