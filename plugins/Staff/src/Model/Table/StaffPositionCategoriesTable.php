<?php
namespace Staff\Model\Table;
use ArrayObject;

use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;

use App\Model\Table\ControllerActionTable;

class StaffPositionCategoriesTable extends ControllerActionTable
{
    public function initialize(array $config)
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

    public function onUpdateFieldType(Event $event, array $attr, $action, Request $request)
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
}