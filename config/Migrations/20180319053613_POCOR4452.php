<?php

use Cake\Utility\Text;
use Phinx\Migration\AbstractMigration;

class POCOR4452 extends AbstractMigration
{
    public function up()
    {
        $contactInstitutionSql = "UPDATE security_functions
        SET `name` = 'Contacts - Institution'
        WHERE `id` = 1047";

        $contactPeopleSql = "UPDATE security_functions
                SET `name` = 'Contacts - People'
                WHERE `id` = 1083";
        
        $this->execute($contactInstitutionSql);
        $this->execute($contactPeopleSql);     

        $labels = [
            [
                'id' => Text::uuid(),
                'module' => 'InstitutionContacts',
                'field' => 'telephone',
                'module_name' => 'Institutions -> Contacts (Institution)',
                'field_name' => 'Telephone',
                'visible' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => Text::uuid(),
                'module' => 'InstitutionContacts',
                'field' => 'fax',
                'module_name' => 'Institutions -> Contacts (Institution)',
                'field_name' => 'Fax',
                'visible' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => Text::uuid(),
                'module' => 'InstitutionContacts',
                'field' => 'email',
                'module_name' => 'Institutions -> Contacts (Institution)',
                'field_name' => 'Email',
                'visible' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => Text::uuid(),
                'module' => 'InstitutionContacts',
                'field' => 'website',
                'module_name' => 'Institutions -> Contacts (Institution)',
                'field_name' => 'Website',
                'visible' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => Text::uuid(),
                'module' => 'InstitutionContactPersons',
                'field' => 'contact_person',
                'module_name' => 'Institutions -> Contacts (People)',
                'field_name' => 'Contact Person',
                'visible' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => Text::uuid(),
                'module' => 'InstitutionContactPersons',
                'field' => 'designation',
                'module_name' => 'Institutions -> Contacts (People)',
                'field_name' => 'Designation',
                'visible' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => Text::uuid(),
                'module' => 'InstitutionContactPersons',
                'field' => 'department',
                'module_name' => 'Institutions -> Contacts (People)',
                'field_name' => 'Department',
                'visible' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => Text::uuid(),
                'module' => 'InstitutionContactPersons',
                'field' => 'telephone',
                'module_name' => 'Institutions -> Contacts (People)',
                'field_name' => 'Telephone',
                'visible' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => Text::uuid(),
                'module' => 'InstitutionContactPersons',
                'field' => 'mobile_number',
                'module_name' => 'Institutions -> Contacts (People)',
                'field_name' => 'Mobile Number',
                'visible' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => Text::uuid(),
                'module' => 'InstitutionContactPersons',
                'field' => 'fax',
                'module_name' => 'Institutions -> Contacts (People)',
                'field_name' => 'Fax',
                'visible' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => Text::uuid(),
                'module' => 'InstitutionContactPersons',
                'field' => 'email',
                'module_name' => 'Institutions -> Contacts (People)',
                'field_name' => 'Email',
                'visible' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => Text::uuid(),
                'module' => 'InstitutionContactPersons',
                'field' => 'preferred',
                'module_name' => 'Institutions -> Contacts (People)',
                'field_name' => 'Preferred',
                'visible' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        
        $this->insert('labels', $labels);
    
    }

    public function down()
    {   
        $contactInstitutionSql = "UPDATE security_functions
        SET `name` = 'Contacts'
        WHERE `id` = 1047";

        $contactPeopleSql = "UPDATE security_functions
                SET `name` = 'Contact Persons'
                WHERE `id` = 1083";

        $this->execute($contactInstitutionSql);
        $this->execute($contactPeopleSql);   

        $this->execute("DELETE FROM `labels` WHERE `module` = 'InstitutionContacts'");
        $this->execute("DELETE FROM `labels` WHERE `module` = 'InstitutionContactPersons'");
    }
}
