<?php
namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Validation\Validator;
use Cake\ORM\Entity;
use Cake\Http\Request;
use Cake\Event\Event;
use ArrayObject ;

class LocalitiesTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('institution_localities');
        parent::initialize($config);

        $this->hasMany('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_locality_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'infrastructure_level') {
            return __('Infrastructure Level');
        } elseif ($field == 'name') {
            return __('Name');
        } elseif ($field == 'default') {
            return __('Default');
        } elseif ($field == 'international_code') {
            return __('International Code');
        } elseif ($field == 'national_code') {
            return __('National Code');
        } elseif ($field == 'visible') {
            return __('Visible');
        } elseif ($field == 'modified_user_id') {
            return __('Modified By');
        } elseif ($field == 'modified') {
            return __('Modified On');
        } elseif ($field == 'created_user_id') {
            return __('Created By');
        } elseif ($field == 'created') {
            return __('Created On');
        } elseif ($field == 'custom_module_id') {
            return __('Custom Module');
        } elseif ($field == 'staff_custom_field_id') {
            return __('Custom Fields');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function beforeDelete(Event $event, Entity $entity)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }
}
