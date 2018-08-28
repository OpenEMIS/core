<?php

use Phinx\Migration\AbstractMigration;

class POCOR4764 extends AbstractMigration
{
    public function up()
    {
        // config_items
        $this->execute('CREATE TABLE `z_4764_config_items` LIKE `config_items`');
        $this->execute('INSERT INTO `z_4764_config_items` SELECT * FROM `config_items`');

        // check if id is correct
        $this->execute('INSERT INTO `config_items` 
            (`id`, `name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `created_user_id`, `created`) VALUES 
            (1019, "Scholarship Institution Choices", "scholarship_institution_choice_type", "Scholarship Institution Choices", "Scholarship Institution Choices", "", "-1", 1, 1, "", "", 1, CURRENT_DATE())');

       // scholarship_institution_choice_types
        $table = $this->table('scholarship_institution_choice_types', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This field options table contains the list of scholarship institution choice types used in scholarships'
            ]);
        $table
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false
            ])
            ->addColumn('visible', 'integer', [
                'default' => 1,
                'limit' => 1,
                'null' => false
            ])
            ->addColumn('editable', 'integer', [
                'default' => 1,
                'limit' => 1,
                'null' => false
            ])
            ->addColumn('default', 'integer', [
                'default' => 0,
                'limit' => 1,
                'null' => false
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false
            ])
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        // scholarship_application_institution_choices
        $this->execute('CREATE TABLE `z_4764_scholarship_application_institution_choices` LIKE `scholarship_application_institution_choices`');
        $this->execute('INSERT INTO `z_4764_scholarship_application_institution_choices` SELECT * FROM `scholarship_application_institution_choices`');
        $this->execute('ALTER TABLE `scholarship_application_institution_choices` MODIFY COLUMN `institution_name`  varchar(150) NULL');
        $this->table('scholarship_application_institution_choices')
            ->addColumn('scholarship_institution_choice_type_id', 'integer', [
                'default' => null,
                'null' => true,
                'after' => 'institution_name',
                'comment' => 'links to scholarship_institution_choice_types.id'
            ])
             ->addIndex('scholarship_institution_choice_type_id')
            ->save();
    }

    public function down()
    {
        $this->dropTable("scholarship_institution_choice_types");

        $this->dropTable("config_items");
        $this->table("z_4764_config_items")->rename("config_items");

        $this->dropTable("scholarship_application_institution_choices");
        $this->table("z_4764_scholarship_application_institution_choices")->rename("scholarship_application_institution_choices");
    }
}
