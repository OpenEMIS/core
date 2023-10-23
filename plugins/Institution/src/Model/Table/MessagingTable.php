<?php

namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;
use ArrayObject;
use Cake\Event\Event;
class MessagingTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        
        $this->table('messaging');
        parent::initialize($config);

        // $this->hasMany('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_ownership_id']);

        // $this->addBehavior('FieldOption.FieldOption');
    }
    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('created',['visible'=>true]);
        $this->field('created_user_id', ['visible' => true]);
        $this->field('recipient_level_id');
        $this->field('recipient_group_id');
        $this->field('subject');
        $this->field('status');
    }
}
