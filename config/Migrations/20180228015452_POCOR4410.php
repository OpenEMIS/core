<?php

use Phinx\Migration\AbstractMigration;

class POCOR4410 extends AbstractMigration
{
     public function up()
    {
        // NEW TABLE FOR INSTITUTION POCs
        $institutionContacts = $this->table('institution_contacts', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all the contacts of the institutions'
        ]);

        $institutionContacts
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
            ->addColumn('institution_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institution.id'
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
            ->save();

            // MIGRATE ALL THE OLD CONTACTS DETAILS OVER FROM INSTITUTION
            $this->execute('INSERT INTO `institution_contacts` (`contact_person`,`institution_id`,`created_user_id`,`created`) SELECT `contact_person`,`id`,`created_user_id`,`created` FROM `institutions` WHERE `contact_person` <> ""');

            $this->execute('CREATE TABLE `z_4410_institutions` LIKE `institutions`');
            $this->execute('INSERT INTO `z_4410_institutions` SELECT * FROM `institutions`');

            // DROP CONTACT PERSON COLUMN IN INSTITUTION
            $table = $this->table('institutions');
            $table->removeColumn('contact_person')
                  ->save();

            // Security function permission
            $contactsSql = "UPDATE security_functions
                           SET `controller` = 'InstitutionContacts',
                           `_view` = 'index|view',
                           `_edit` = 'edit',
                           `_add` = 'add',
                           `_delete` = 'delete'
                           WHERE `id` = 1047";

            $this->execute($contactsSql);

            $records = [
                [   'id' => '1010',
                    'name' => 'Institution Contact Telephone',
                    'code' => 'institution_contact_telephone',
                    'type' => 'Custom Validation',
                    'label' => 'Institution Contact Telephone',
                    'value' => '',
                    'default_value' => '',
                    'editable' => '1',
                    'visible' => '1',
                    'field_type' => '',
                    'option_type' => '',
                    'modified_user_id' => '108',
                    'modified' => '2014-04-02 16:48:24',
                    'created_user_id' => '0',
                    'created' => '1970-01-01 00:00:00'
                ],
                [
                    'id' => '1011',
                    'name' => 'Institution Contact Mobile',
                    'code' => 'institution_contact_mobile',
                    'type' => 'Custom Validation',
                    'label' => 'Institution Contact Mobile',
                    'value' => '',
                    'default_value' => '',
                    'editable' => '1',
                    'visible' => '1',
                    'field_type' => '',
                    'option_type' => '',
                    'modified_user_id' => '108',
                    'modified' => '2014-04-02 16:48:24',
                    'created_user_id' => '0',
                    'created' => '1970-01-01 00:00:00'
                ],
                [
                    'id' => '1012',
                    'name' => 'Institution Contact Fax',
                    'code' => 'institution_contact_fax',
                    'type' => 'Custom Validation',
                    'label' => 'Institution Contact Fax',
                    'value' => '',
                    'default_value' => '',
                    'editable' => '1',
                    'visible' => '1',
                    'field_type' => '',
                    'option_type' => '',
                    'modified_user_id' => '108',
                    'modified' => '2014-04-02 16:48:24',
                    'created_user_id' => '0',
                    'created' => '1970-01-01 00:00:00'
                ],
            ];

            $this->insert('config_items', $records);
        }
 

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `institutions`');
        $this->execute('RENAME TABLE `z_4410_institutions` TO `institutions`');
        $this->execute('DROP TABLE IF EXISTS `institution_contacts`');

        $contactsSql = "UPDATE security_functions
                        SET `controller` = 'Institutions',
                        `_view` = 'Contacts.index|Contacts.view',
                        `_edit` = 'Contacts.edit',
                        `_add` = NULL,
                        `_delete` = NULL
                        WHERE `id` = 1047";
        
        $this->execute($contactsSql);      
        $this->execute('DELETE FROM `config_items` WHERE `id` = 1010');
        $this->execute('DELETE FROM `config_items` WHERE `id` = 1011');
        $this->execute('DELETE FROM `config_items` WHERE `id` = 1012');
    }
}
