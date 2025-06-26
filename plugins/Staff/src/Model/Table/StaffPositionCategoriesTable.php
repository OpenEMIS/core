<?php
namespace Staff\Model\Table;
use ArrayObject;

use Cake\Event\Event;
use Cake\Http\ServerRequest;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;

use App\Model\Table\ControllerActionTable;

class StaffPositionCategoriesTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->addBehavior('FieldOption.FieldOption');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'StaffPositionCategories' => ['index', 'view']
        ]);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('type', ['after' => 'editable', 'type' => 'select']);
    }

    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('type', ['after' => 'name']);
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('type', ['after' => 'name']);
    }

    public function onUpdateFieldType(Event $event, array $attr, $action, ServerRequest $request)
    {
        
        $options = [
            1 => __('Teaching'),
            0 => __('Non Teaching')
        ]; //POCOR-6950
        $attr['type'] = 'select';
        $attr['select'] = false;
        $attr['options'] = $options;
        
        return $attr;
        
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
            case 'type':
                return __('Teacher');
            default:
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}