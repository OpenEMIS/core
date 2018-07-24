<?php

use Phinx\Migration\AbstractMigration;

class POCOR4410 extends AbstractMigration
{
     public function up()
    {
        // NEW TABLE FOR INSTITUTION POCs
        $institutionContactPersons = $this->table('institution_contact_persons', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all the contact persons of the institutions'
        ]);

        $institutionContactPersons
            ->addColumn('contact_person', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false
            ])
            ->addColumn('designation', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => true
            ])
            ->addColumn('department', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => true
            ])

            ->addColumn('telephone', 'string', [
                'default' => null,
                'limit' => 30,
                'null' => true
            ])
            ->addColumn('mobile_number', 'string', [
                'default' => null,
                'limit' => 30,
                'null' => true
            ])
            ->addColumn('fax', 'string', [
                'default' => null,
                'limit' => 30,
                'null' => true
            ])
            ->addColumn('email', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => true
            ])
            ->addColumn('preferred', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institutions.id'
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
            ->addIndex('institution_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

            // MIGRATE ALL THE OLD CONTACTS DETAILS OVER FROM INSTITUTION AND SET THEM AS PREFERRED
            $this->execute('INSERT INTO `institution_contact_persons` (`contact_person`, `preferred`, `institution_id`, `created_user_id`, `created`) SELECT `contact_person`, 1, `id`, `created_user_id`, `created` FROM `institutions` WHERE `contact_person` <> ""');
            
            // Security function permission
            $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 2');
          
            $this->insert('security_functions', [
                'id' => 1083,
                'name' => 'Contact Persons',
                'controller' => 'InstitutionContactPersons',
                'module' => 'Institutions',
                'category' => 'General',
                'parent_id' => 8,
                '_view' => 'index|view',
                '_edit' => 'edit',
                '_add' => 'add',
                '_delete' => 'delete',
                'order' => 3,
                'visible' => 1,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]);


            $records = [
                [   'id' => '1010',
                    'name' => 'Institution Contact Person Telephone',
                    'code' => 'institution_contact_person_telephone',
                    'type' => 'Custom Validation',
                    'label' => 'Institution Contact Person Telephone',
                    'value' => '',
                    'default_value' => '',
                    'editable' => '1',
                    'visible' => '1',
                    'field_type' => '',
                    'option_type' => '',
                    'modified_user_id' => null,
                    'modified' => null,
                    'created_user_id' => '1',
                    'created' => date('Y-m-d H:i:s')
                ],
                [
                    'id' => '1011',
                    'name' => 'Institution Contact Person Mobile',
                    'code' => 'institution_contact_person_mobile',
                    'type' => 'Custom Validation',
                    'label' => 'Institution Contact Person Mobile',
                    'value' => '',
                    'default_value' => '',
                    'editable' => '1',
                    'visible' => '1',
                    'field_type' => '',
                    'option_type' => '',
                    'modified_user_id' => null,
                    'modified' => null,
                    'created_user_id' => '1',
                    'created' => date('Y-m-d H:i:s')
                ],
                [
                    'id' => '1012',
                    'name' => 'Institution Contact Person Fax',
                    'code' => 'institution_contact_person_fax',
                    'type' => 'Custom Validation',
                    'label' => 'Institution Contact Person Fax',
                    'value' => '',
                    'default_value' => '',
                    'editable' => '1',
                    'visible' => '1',
                    'field_type' => '',
                    'option_type' => '',
                    'modified_user_id' => null,
                    'modified' => null,
                    'created_user_id' => '1',
                    'created' => date('Y-m-d H:i:s')
                ],
            ];

            $this->insert('config_items', $records);
    }
 

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `institution_contact_persons`');
        $this->execute('UPDATE security_functions SET `order` = `order` - 1 WHERE `order` > 2');
        $this->execute('DELETE FROM security_functions WHERE id = 1083');    
        $this->execute('DELETE FROM `config_items` WHERE `id` = 1010');
        $this->execute('DELETE FROM `config_items` WHERE `id` = 1011');
        $this->execute('DELETE FROM `config_items` WHERE `id` = 1012');
    }
}   
