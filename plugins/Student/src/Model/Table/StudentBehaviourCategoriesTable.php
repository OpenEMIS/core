<?php
namespace Student\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;

use App\Model\Table\ControllerActionTable;

class StudentBehaviourCategoriesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('student_behaviour_categories');
        parent::initialize($config);

        $this->hasMany('StudentBehaviours', ['className' => 'Student.StudentBehaviours', 'foreignKey' => 'student_behaviour_category_id']);

        $this->belongsTo('BehaviourClassifications', ['className' => 'Student.BehaviourClassifications', 'foreignKey' => 'behaviour_classification_id']);

        $this->addBehavior('FieldOption.FieldOption');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'OpenEMIS_Classroom' => ['index']
        ]);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('behaviour_classification_id', ['after' => 'editable', 'type' => 'select']);
    }

    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('behaviour_classification_id', ['after' => 'name']);
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('behaviour_classification_id', ['after' => 'name']);
    }

    public function getUnusedStudentBehaviourCategories($id)
    {
        if (!empty($id)) {
            $where = [
                'OR' => [
                    [$this->aliasField('behaviour_classification_id') => $id],
                    [$this->aliasField('behaviour_classification_id') => 0]
                ]
            ];
        } else {
            $where = [$this->aliasField('behaviour_classification_id') => 0];
        }

        $unusedList = $this
            ->find('list')
            ->where($where)
            ->order('order')
            ->toArray();

        return $unusedList;
    }

}
