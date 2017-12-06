<?php
use Migrations\AbstractMigration;

class POCOR4255 extends AbstractMigration
{
    // commit
    public function up()
    {
        // infrastructure_wash_hygiene_types
        $table = $this->table('infrastructure_wash_hygiene_types', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This field options table contains types of infrastructure wash hygiene types'
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
        // end infrastructure_wash_hygiene_types

        //infrastructure_wash_hygiene_soapash_availabilities
        $table = $this->table('infrastructure_wash_hygiene_soapash_availabilities', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This field options table contains types of infrastructure wash hygiene soap/ash avaibilities'
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
        // end infrastructure_wash_hygiene_soapash_availabilities

        // infrastructure_wash_hygiene_educations
        $table = $this->table('infrastructure_wash_hygiene_educations', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This field options table contains types of infrastructure wash hygiene educations'
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
        // end infrastructure_wash_hygiene_educations

        // infrastructure_wash_hygienes
        $table = $this->table('infrastructure_wash_hygienes', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This table contains infrastructure hygienes'
            ]);
        $table
            ->addColumn('academic_period_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to academic_periods.id'
            ])
            ->addColumn('institution_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institutions.id'
            ])
            ->addColumn('infrastructure_wash_hygiene_type_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to infrastructure_wash_hygiene_types.id'
            ])
            ->addColumn('infrastructure_wash_hygiene_soapash_availability_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to infrastructure_wash_hygiene_soapash_availabilities.id'
            ])
            ->addColumn('infrastructure_wash_hygiene_education_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to infrastructure_wash_hygiene_educations.id'
            ])
            ->addColumn('infrastructure_wash_hygiene_total_male', 'integer', [
                'default' => 0,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('infrastructure_wash_hygiene_total_female', 'integer', [
                'default' => 0,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('infrastructure_wash_hygiene_total_mixed', 'integer', [
                'default' => 0,
                'limit' => 11,
                'null' => false
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
            ->addIndex('academic_period_id')
            ->addIndex('institution_id')
            ->addIndex('infrastructure_wash_hygiene_type_id')
            ->addIndex('infrastructure_wash_hygiene_soapash_availability_id')
            ->addIndex('infrastructure_wash_hygiene_education_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        // end infrastructure_wash_hygienes

        // infrastructure_wash_hygiene_quantities
        $table = $this->table('infrastructure_wash_hygiene_quantities', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This field options table contains details of infrastructure wash hygiene quantities'
            ]);
        $table
            ->addColumn('gender_id', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('functional', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('value', 'integer', [
                'default' => 0,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('infrastructure_wash_hygiene_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to infrastructure_wash_hygienes.id'
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
            ->addIndex('infrastructure_wash_hygiene_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        // end infrastructure_wash_hygiene_quantities

        // security_functions
        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 14');

        $this->insert('security_functions', [
            'id' => 1075,
            'name' => 'Infrastructure WASH Hygienes',
            'controller' => 'InfrastructureWashHygienes',
            'module' => 'Institutions',
            'category' => 'Details',
            'parent_id' => 8,
            '_view' => 'index|view|download',
            '_edit' => 'edit',
            '_add' => 'add',
            '_delete' => 'delete',
            'order' => 15,
            'visible' => 1,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);
        // end security_functions
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE infrastructure_wash_hygiene_types');
        $this->execute('DROP TABLE infrastructure_wash_hygiene_soapash_availabilities');
        $this->execute('DROP TABLE infrastructure_wash_hygiene_educations');
        $this->execute('DROP TABLE infrastructure_wash_hygienes');
        $this->execute('DROP TABLE infrastructure_wash_hygiene_quantities');
        $this->execute('UPDATE security_functions SET `order` = `order` - 1 WHERE `order` > 14');
        $this->execute('DELETE FROM security_functions WHERE id = 1075');
    }
}
