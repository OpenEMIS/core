<?php
namespace App\Form;

use Cake\Form\Form;
use Cake\Form\Schema;
use Cake\Validation\Validator;

/**
 * DatabaseInstaller Form.
 */
class DatabaseConnectionForm extends Form
{
    /**
     * Builds the schema for the modelless form
     *
     * @param \Cake\Form\Schema $schema From schema
     * @return \Cake\Form\Schema
     */
    protected function _buildSchema(Schema $schema)
    {
        return $schema->addField('database_server_host', ['type' => 'string'])
            ->addField('database_server_port', ['type' => 'string'])
            ->addField('admin_user', ['type' => 'string'])
            ->addField('admin_password', ['type' => 'password']);
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
            ->requirePresence('database_server_host')
            ->requirePresence('database_server_port')
            ->requirePresence('admin_user')
            ->requirePresence('admin_password');
    }

    /**
     * Defines what to execute once the From is being processed
     *
     * @param array $data Form data.
     * @return bool
     */
    protected function _execute(array $data)
    {
        // execute creation logic
        return true;
    }
}
