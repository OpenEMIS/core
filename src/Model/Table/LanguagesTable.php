<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class LanguagesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('languages');
        parent::initialize($config);

        $this->hasMany('UserLanguages', ['className' => 'UserLanguages', 'foreignKey' => 'language_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }
}
