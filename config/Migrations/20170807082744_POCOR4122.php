<?php

use Phinx\Migration\AbstractMigration;

class POCOR4122 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function up()
    {
        // qualification_specialisations
        $table = $this->table('qualification_specialisations', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This table contains the specialisations of the qualifications'
            ]);

        $table->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
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
            ->addColumn('education_field_of_study_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
                'comment' => 'links to education_field_of_studies.id'
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
            ->addIndex('education_field_of_study_id')
            ->save();
        // qualification_specialisations

        //labels
        $sql = "UPDATE `labels` 
                SET `field_name` = 'Subjects', 
                `modified_user_id` = '1', 
                `modified` = '2017-08-07 00:00:00' 
                WHERE `module` = 'Qualifications'
                AND `module_name` = 'Qualifications'
                AND `field` = 'education_subjects'";

        $this->execute($sql);

        $sql = "UPDATE `labels` 
                SET `field` = 'qualification_country_id', 
                `modified_user_id` = '1', 
                `modified` = '2017-08-07 00:00:00' 
                WHERE `module` = 'Qualifications'
                AND `module_name` = 'Qualifications'
                AND `field_name` = 'Institution Country'";

        $this->execute($sql);

        $sql = "UPDATE `labels` 
                SET `field_name` = 'Country', 
                `modified_user_id` = '1', 
                `modified` = '2017-08-07 00:00:00' 
                WHERE `module` = 'Qualifications'
                AND `module_name` = 'Qualifications'
                AND `field` = 'qualification_country_id'";

        $this->execute($sql);

        $table = $this->table('labels');

        $data = [
            [
                'id' => 'f7c04df1-7d71-11e7-b383-525400b263eb',
                'module' => 'Qualifications',
                'field' => 'education_field_of_study_id',
                'module_name' => 'Qualifications',
                'field_name' => 'Field Of Study',
                'code' => NULL,
                'name' => NULL,
                'visible' => '1',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => '059c46e4-7d72-11e7-b383-525400b263eb',
                'module' => 'Qualifications',
                'field' => 'qualification_specialisations',
                'module_name' => 'Qualifications',
                'field_name' => 'Specialisations',
                'code' => NULL,
                'name' => NULL,
                'visible' => '1',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => '0b600407-7d72-11e7-b383-525400b263eb',
                'module' => 'Qualifications',
                'field' => 'qualification_title_id',
                'module_name' => 'Qualifications',
                'field_name' => 'Title',
                'code' => NULL,
                'name' => NULL,
                'visible' => '1',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => '124ac8d5-7d72-11e7-b383-525400b263eb',
                'module' => 'Qualifications',
                'field' => 'qualification_institution',
                'module_name' => 'Qualifications',
                'field_name' => 'Institution',
                'code' => NULL,
                'name' => NULL,
                'visible' => '1',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ]
        ];

        $this->insert('labels', $data);
        //labels

        // staff_qualifications_specialisations
        $table = $this->table('staff_qualifications_specialisations', [
                'id' => false,
                'primary_key' => ['staff_qualification_id','qualification_specialisation_id'],
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This table contains the relation between staff qualification and its specialisations'
        ]);

        $table->addColumn('id', 'string', [
                'limit' => 64,
                'null' => false
            ])
            ->addColumn('staff_qualification_id', 'integer', [
                'limit' => 11,
                'comment' => 'links to staff_qualifications.id'
            ])
            ->addColumn('qualification_specialisation_id', 'integer', [
                'limit' => 11,
                'comment' => 'links to qualification_specialisations.id'
            ])
            ->addIndex('staff_qualification_id')
            ->addIndex('qualification_specialisation_id')
            ->save();
        // staff_qualifications_specialisations
    }

    public function down()
    {
        $this->execute('DROP TABLE qualification_specialisations');
        $this->execute("DELETE FROM labels WHERE id = 'f7c04df1-7d71-11e7-b383-525400b263eb'");
        $this->execute("DELETE FROM labels WHERE id = '059c46e4-7d72-11e7-b383-525400b263eb'");
        $this->execute("DELETE FROM labels WHERE id = '0b600407-7d72-11e7-b383-525400b263eb'");
        $this->execute("DELETE FROM labels WHERE id = '124ac8d5-7d72-11e7-b383-525400b263eb'");
        $this->execute('DROP TABLE staff_qualifications_specialisations');
    }
}
