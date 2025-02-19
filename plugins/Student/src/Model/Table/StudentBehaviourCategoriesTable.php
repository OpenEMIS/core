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
    public function initialize(array $config): void
    {
        $this->setTable('student_behaviour_categories');
        parent::initialize($config);

        $this->hasMany('StudentBehaviours', ['className' => 'Student.StudentBehaviours', 'foreignKey' => 'student_behaviour_category_id']);

        // $this->belongsTo('BehaviourClassifications', ['className' => 'Student.StudentBehaviourClassifications', 'foreignKey' => 'behaviour_classification_id']);//POCOR-8866

        $this->addBehavior('FieldOption.FieldOption');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'OpenEMIS_Classroom' => ['index']
        ]);

        $this->setDeleteStrategy('restrict');
    }
    //POCOR-8866 start

    // public function beforeAction(Event $event, ArrayObject $extra)
    // {
    //    $this->field('behaviour_classification_id', ['after' => 'editable', 'type' => 'select']);
    // }

    // public function addEditBeforeAction(Event $event, ArrayObject $extra)
    // {
    //     $this->field('behaviour_classification_id', ['after' => 'name']);
    // }

    // public function viewBeforeAction(Event $event, ArrayObject $extra)
    // {
    //     $this->field('behaviour_classification_id', ['after' => 'name']);
    // }

    //POCOR-8866 end
    public function getUnusedStudentBehaviourCategories($id)
    {
        //POCOR-8866 start
        // if (!empty($id)) {
        //     $where = [
        //         'OR' => [
        //             [$this->aliasField('behaviour_classification_id') => $id],
        //             [$this->aliasField('behaviour_classification_id') => 0]
        //         ]
        //     ];
        // } else {
        //     $where = [$this->aliasField('behaviour_classification_id') => 0];
        // }
        //POCOR-8866 end
        $unusedList = $this
            ->find('list')
            // ->where($where)
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
        $behaviorCategory = TableRegistry::getTableLocator()->get('student_behaviours');

        $data = $behaviorCategory->find()->where(['student_behaviour_category_id'=>$categoryId])->count(); 
        if($data > 0)
        {
            return true;
        }else{
            return false;
        }
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
        $connection->execute('SET FOREIGN_KEY_CHECKS = 0');
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
            // case 'behaviour_classification_id': POCOR-8866
            //     return __('Behaviour Classification');
            default:
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

}
