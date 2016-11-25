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

class StudentBehaviourClassificationsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('student_behaviour_classifications');
        parent::initialize($config);

        // $this->hasMany('StudentBehaviourCategories', ['className' => 'Student.StudentBehaviourCategories', 'foreignKey' => 'student_behaviour_category_id']);

        $this->belongsToMany('StudentBehaviourCategories', [
            'className' => 'Student.StudentBehaviourCategories',
            'joinTable' => 'student_behaviour_categories_classifications',
            'foreignKey' => 'student_behaviour_classification_id',
            'targetForeignKey' => 'student_behaviour_category_id',
            'through' => 'Student.StudentBehaviourCategoriesClassifications',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->addBehavior('FieldOption.FieldOption');
    }

    public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        //this will exclude checking during remove restrict
        $extra['excludedModels'] = [
            $this->StudentBehaviourCategories->alias()
        ];
    }

    public function beforeAction(Event $event, arrayObject $extra)
    {
        $this->field('default', ['visible' => false]);
        $this->field('editable', ['visible' => false]);
        $this->field('international_code', ['visible' => false]);
        $this->field('national_code', ['visible' => false]);
        $this->field('student_behaviour_categories', ['after' => 'visible']);
    }

    public function indexBeforeAction(Event $event, arrayObject $extra)
    {
        $this->field('default', ['visible' => false]);
        $this->field('editable', ['visible' => false]);
        $this->field('international_code', ['visible' => false]);
        $this->field('national_code', ['visible' => false]);
    }

    public function viewEditBeforeQuery(Event $event, Query $query)
    {
        $query->contain(['StudentBehaviourCategories']);
    }

    public function onUpdateFieldStudentBehaviourCategories(Event $event, array $attr, $action, Request $request)
    {
        $id = $this->paramsPass(0);

        switch ($action) {
            case 'add':
            case 'edit':
                $behaviourOptions = $this->getStudentBehaviourCategoriesOptions($id);

                $attr['type'] = 'chosenSelect';
                $attr['options'] = $behaviourOptions;
                break;
            default:
                # code...
                break;
        }

        return $attr;
    }

    public function onGetStudentBehaviourCategories(Event $event, Entity $entity)
    {
        if (!$entity->has('student_behaviour_categories')) {
            $query = $this->find()
            ->where([$this->aliasField($this->primaryKey()) => $entity->id])
            ->contain(['StudentBehaviourCategories'])
            ;
            $data = $query->first();
        }
        else {
            $data = $entity;
        }

        $behaviourCategories = [];
        if ($data->has('student_behaviour_categories')) {
            foreach ($data->student_behaviour_categories as $key => $value) {
                $behaviourCategories[] = $value->name;
            }
        }

        return (!empty($behaviourCategories))? implode(', ', $behaviourCategories): ' ';
    }

    public function getStudentBehaviourCategoriesOptions($id)
    {
        $StudentBehaviourCategoriesClassifications = TableRegistry::get('Student.StudentBehaviourCategoriesClassifications');

        // get the list of used behaviour
        $usedBehaviourCategoriesArray = $StudentBehaviourCategoriesClassifications->getUsedBehaviourCategories($id);

        $behaviourOptionsData = $this->StudentBehaviourCategories
            ->find('list')
            ->select([
                $this->StudentBehaviourCategories->aliasField($this->StudentBehaviourCategories->primaryKey()),
                $this->StudentBehaviourCategories->aliasField('name')
            ])
            ->find('visible')
            ->find('order')
            ->toArray();

        // only behaviour not in the used list will appeared in the behaviour list
        $behaviourOptions = [];
        foreach ($behaviourOptionsData as $key => $obj) {
            if (!array_key_exists($key, $usedBehaviourCategoriesArray)) {
                $behaviourOptions [$key] = $obj;
            }
        }

        return $behaviourOptions;
    }

    public function findStudentBehaviourClassificationsOptions(Query $query, array $options)
    {
        return $query
            ->find('list')
            ->find('visible')
            ->order([$this->aliasField('order')])
        ;
    }
}
