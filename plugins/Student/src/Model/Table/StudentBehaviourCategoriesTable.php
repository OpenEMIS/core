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

        $this->setDeleteStrategy('restrict');
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

    /**
     * POCOR-7196
    **/ 
    public function onBeforeDelete(Event $event, Entity $entity, ArrayObject $extra)
    {

        if($this->checkStudentRecords($entity)) {
            $this->Alert->error('general.delete.restrictDeleteBecauseAssociation', ['reset'=>true]);
            $event->stopPropagation();
            return $this->controller->redirect($this->url('remove'));
        }
    }

    /**
     * POCOR-7196
    **/
    public function checkStudentRecords($entity)
    {
        $categoryId = $entity->id ?? 0;
        $behaviorCategory = TableRegistry::get('student_behaviours');

        $data = $behaviorCategory->find()->where(['student_behaviour_category_id'=>$categoryId])->count(); 
        if($data > 0)
        {
            return true;
        }else{
            return false;
        }
    }

}
