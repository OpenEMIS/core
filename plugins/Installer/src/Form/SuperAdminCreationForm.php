<?php
namespace Installer\Form;

use Cake\Form\Form;
use Cake\Form\Schema;
use Cake\Validation\Validator;
use Cake\ORM\TableRegistry;
use Cake\I18n\Date;

/**
 * SuperAdminCreation Form.
 */
class SuperAdminCreationForm extends Form
{
    /**
     * Builds the schema for the modelless form
     *
     * @param \Cake\Form\Schema $schema From schema
     * @return \Cake\Form\Schema
     */
    protected function _buildSchema(Schema $schema)
    {
        return $schema->addField('username', ['type' => 'string'])
            ->addField('password', ['type' => 'string'])
            ->addField('area_name', ['type' => 'string'])
            ->addField('area_code', ['type' => 'string']);
    }

    /**
     * Form validation builder
     *
     * @param \Cake\Validation\Validator $validator to use against the form
     * @return \Cake\Validation\Validator
     */
    protected function _buildValidator(Validator $validator)
    {
        return $validator
            ->requirePresence('account_password')
            ->requirePresence('retype_password')
            ->add('account_password', [
                'compare' => [
                    'rule' => ['compareWith', 'retype_password'],
                    'message' => 'Passwords entered does not match.'
                ]
            ])
            ->requirePresence('area_code')
            ->requirePresence('area_name');
    }

    /**
     * Defines what to execute once the From is being processed
     *
     * @param array $data Form data.
     * @return bool
     */
    protected function _execute(array $data)
    {

        $password = $data['account_password'];
        $name = $data['area_name'];
        $code = $data['area_code'];
        return ($this->createUser($password) && $this->createArea($name, $code));
    }

    private function createUser($password)
    {
        $UserTable = TableRegistry::get('User.Users');
        $data = [
            'id' => 1,
            'username' => 'admin',
            'password' => $password,
            'openemis_no' => 'sysadmin',
            'first_name' => 'System',
            'middle_name' => null,
            'third_name' => null,
            'last_name' => 'Administrator',
            'preferred_name' => null,
            'email' => null,
            'address' => null,
            'postal_code' => null,
            'address_area_id' => null,
            'birthplace_area_id' => null,
            'gender_id' => 1,
            'date_of_birth' => new Date(),
            'date_of_death' => null,
            'nationality_id' => null,
            'identity_type_id' => null,
            'identity_number' => null,
            'external_reference' => null,
            'super_admin' => 1,
            'status' => 1,
            'last_login' => new Date(),
            'photo_name' => null,
            'photo_content' => null,
            'preferred_language' => 'en',
            'is_student' => 0,
            'is_staff' => 0,
            'is_guardian' => 0
        ];

        $entity = $UserTable->newEntity($data, ['validate' => false]);
        return $UserTable->save($entity);
    }

    private function createArea($name, $code)
    {
        $AreasTable = TableRegistry::get('Area.Areas');
        $data = [
            'id' => 1,
            'code' => $code,
            'name' => $name,
            'parent_id' => null,
            'lft' => 1,
            'rght' => 2,
            'area_level_id' => 1,
            'order' => 1,
            'visible' => 1
        ];
        $entity = $AreasTable->newEntity($data);
        return $AreasTable->save($entity);
    }
}
