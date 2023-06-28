<?php
namespace Student\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;

use App\Model\Table\ControllerActionTable;
//POCOR-7223
class StudentBehaviourClassificationsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {

        $this->table('student_behaviour_classifications');
        parent::initialize($config);

        $this->hasMany('StudentBehaviours', ['className' => 'Student.StudentBehaviours', 'foreignKey' => 'student_behaviour_classification_id']);

        $this->addBehavior('FieldOption.FieldOption');


    }
}