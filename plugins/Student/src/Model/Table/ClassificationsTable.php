<?php
namespace Student\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

use App\Model\Table\ControllerActionTable;

class ClassificationsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('classifications');
        parent::initialize($config);

        $this->hasMany('StudentBehaviourCategories', ['className' => 'Student.StudentBehaviourCategories', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->addBehavior('FieldOption.FieldOption');
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('default', ['visible' => false]);
        $this->field('editable', ['visible' => false]);
        $this->field('international_code', ['visible' => false]);
        $this->field('national_code', ['visible' => false]);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('default', ['visible' => false]);
        $this->field('editable', ['visible' => false]);
        $this->field('international_code', ['visible' => false]);
        $this->field('national_code', ['visible' => false]);
    }

    public function getThresholdOptions()
    {
        return $this
            ->find('list')
            ->find('visible')
            ->order([$this->aliasField('order')])
            ->toArray()
        ;
    }
}
