<?php
namespace FieldOption\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Validation\Validator;
use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;

class InfrastructureConditionsTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('infrastructure_conditions');
        parent::initialize($config);

        $this->hasMany('InstitutionLands', ['className' => 'Institution.InstitutionLands', 'foreignKey' => 'infrastructure_condition_id']);
        $this->hasMany('InstitutionBuildings', ['className' => 'Institution.InstitutionBuildings', 'foreignKey' => 'infrastructure_condition_id']);
        $this->hasMany('InstitutionFloors', ['className' => 'Institution.InstitutionFloors', 'foreignKey' => 'infrastructure_condition_id']);
        $this->hasMany('InstitutionRooms', ['className' => 'Institution.InstitutionRooms', 'foreignKey' => 'infrastructure_condition_id']);

        $this->addBehavior('FieldOption.FieldOption');
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

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'modified':
                return __('Modified');
            case 'modified_user_id':
                return __('Modified By');
            case 'created':
                return __('Created');
            case 'created_user_id':
                return __('Created By');
            case 'visible':
                return __('Visible');
            case 'name':
                return __('Name');
            case 'international_code':
                return __('International Code');
            case 'national_code':
                return __('National Code');
            case 'editable':
                return __('Editable');
            case 'default':
                return __('Default');
            default:
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}
