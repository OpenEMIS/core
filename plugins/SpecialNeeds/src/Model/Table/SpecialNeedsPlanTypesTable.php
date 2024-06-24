<?php
namespace SpecialNeeds\Model\Table;

use App\Model\Table\ControllerActionTable;
use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;

class SpecialNeedsPlanTypesTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->addBehavior('FieldOption.FieldOption');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'PlanTypes' => ['index', 'view']
        ]);
    }

    public function getPlanTypeList($params = [])
    {

        $data = $this
            ->find('list')
            ->toArray();
        return $data;
    }
    // Start POCOR-7286
    public function beforeAction() {
        $this->field('name', ['length' => 75]);
    }
    // End POCOR-7286


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
