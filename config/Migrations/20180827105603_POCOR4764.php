<?php

use Phinx\Migration\AbstractMigration;
use Cake\ORM\TableRegistry;

class POCOR4764 extends AbstractMigration
{
    public function up()
    {
        $ApplicationInstitutionChoices = TableRegistry::get('Scholarship.ApplicationInstitutionChoices');
        $ScholarshipApplicationInstitutionChoices = $ApplicationInstitutionChoices
            ->find()
            ->toArray();

        $institutionNameData = $ApplicationInstitutionChoices
            ->find()
            ->distinct(['institution_name'])
            ->select('institution_name')
            ->toArray();

        // Create field options for scholarship_institution_choice_types based on the existing institution_name column in scholarship_application_institution_choices table 
        $data = [];
        $institutionNames = [];
        $i = 1;

        if (!empty($institutionNameData)) {
            foreach ($institutionNameData as $key => $value) {
                $institutionNames[$i] = $value['institution_name'];
                $data[] = [
                    'name' => $value['institution_name'],
                    'order' => $i,
                    'modified_user_id' => NULL,
                    'modified' => NULL,
                    'created_user_id' => '1',
                    'created' => date('Y-m-d H:i:s')
                ];
                $i++;
            }
        }

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
        if (!empty($data)) {
            $this->insert('scholarship_institution_choice_types', $data);
        }

        // Backup scholarship_application_institution_choices
        $this->execute('CREATE TABLE `z_4764_scholarship_application_institution_choices` LIKE `scholarship_application_institution_choices`');
        $this->execute('INSERT INTO `z_4764_scholarship_application_institution_choices` SELECT * FROM `scholarship_application_institution_choices`');
        // Remove institution_name column from scholarship_application_institution_choices
        $this->execute('ALTER TABLE `scholarship_application_institution_choices` DROP COLUMN `institution_name`');
        // Remove scholarship_institution_choice_type_id column from scholarship_application_institution_choices
        $this->table('scholarship_application_institution_choices')
            ->addColumn('scholarship_institution_choice_type_id', 'integer', [
                'default' => null,
                'null' => false,
                'after' => 'location_type',
                'comment' => 'links to scholarship_institution_choice_types.id'
            ])
             ->addIndex('scholarship_institution_choice_type_id')
            ->save();
        // Generates all the update queries to scholarship_institution_choice_type_id column.
        if(!empty($ScholarshipApplicationInstitutionChoices)) {
            $sql = '';
            foreach ($ScholarshipApplicationInstitutionChoices as $key => $value) {

                $scholarshipInstitutionChoiceTypeId = array_search($value->institution_name, $institutionNames);
                $sql .= 'UPDATE `scholarship_application_institution_choices` SET `scholarship_institution_choice_type_id` ='. $scholarshipInstitutionChoiceTypeId.' WHERE `id` ='.$value->id.';';
            }
            $this->execute($sql);
        }
    }

    public function down()
    {
        $this->dropTable("scholarship_institution_choice_types");

        $this->dropTable("scholarship_application_institution_choices");
        $this->table("z_4764_scholarship_application_institution_choices")->rename("scholarship_application_institution_choices");
    }
}
