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
        
        $this->execute("CREATE TABLE `institution_classes_custom_field_values` (
            `id` char(36) NOT NULL,
            `text_value` varchar(250) DEFAULT NULL,
            `number_value` int(11) DEFAULT NULL,
            `decimal_value` varchar(25) DEFAULT NULL,        
            `textarea_value` text DEFAULT NULL,
            `date_value` date DEFAULT NULL,
            `time_value` time DEFAULT NULL,
            `file` longblob DEFAULT NULL,
            `institution_custom_field_id` int(11) NOT NULL,
            `institution_class_id` int(11) NOT NULL,
            `modified_user_id` int(11) DEFAULT NULL,
            `modified` datetime DEFAULT NULL,
            `created_user_id` int(11) NOT NULL,
            `created` datetime NOT NULL,
            PRIMARY KEY (`id`),
            KEY `institution_custom_field_id` (`institution_custom_field_id`),
            KEY `institution_class_id` (`institution_class_id`),
            CONSTRAINT `fk_custom_field` FOREIGN KEY (`institution_custom_field_id`) REFERENCES `institution_custom_fields` (`id`),
            CONSTRAINT `fk_institution_class` FOREIGN KEY (`institution_class_id`) REFERENCES `institution_classes` (`id`)
            "
        );
        
        
    }
    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `custom_modules`');
        $this->execute('RENAME TABLE `z_8538_custom_modules` TO `custom_modules`');
        $this->execute('DROP TABLE IF EXISTS `institution_classes_custom_field_values` ');
    }

}
