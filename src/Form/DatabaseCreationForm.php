<?php
namespace App\Form;

use Cake\Form\Form;
use Cake\Form\Schema;
use Cake\Validation\Validator;

/**
 * DatabaseCreation Form.
 */
class DatabaseCreationForm extends Form
{
    /**
     * Builds the schema for the modelless form
     *
     * @param \Cake\Form\Schema $schema From schema
     * @return \Cake\Form\Schema
     */
    protected function _buildSchema(Schema $schema)
    {
        return $schema->addField('database_name', ['type' => 'string'])
            ->addField('database_login', ['type' => 'string'])
            ->addField('database_password', ['type' => 'string'])
            ->addField('database_password_confirm', ['type' => 'string']);
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
            ->requirePresence('database_name')
            ->requirePresence('database_login')
            ->requirePresence('database_password')
            ->requirePresence('database_password_confirm');
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
