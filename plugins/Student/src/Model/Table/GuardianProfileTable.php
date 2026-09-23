<?php

namespace Student\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

/**
 * Read/edit view of a guardian's own profile details (gender, date of birth, email, mobile
 * number, nationality), reached from Institution > Students > Guardians > View Profile.
 * POCOR-9811
 */
class GuardianProfileTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('security_users');
        parent::initialize($config);
        $this->setEntityClass('User.User');

        $this->belongsTo('Genders', ['className' => 'User.Genders']);
        $this->belongsTo('MainNationalities', ['className' => 'FieldOption.Nationalities', 'foreignKey' => 'nationality_id']);

        $this->addBehavior('User.User');
        $this->addBehavior('ControllerAction.Image');
        $this->addBehavior('Institution.InstitutionTab', [
            'implementedMethods' => [
                'setUserTabElements' => 'setUserTabElements',
            ],
        ]);

        $this->toggle('index', false);
        $this->toggle('add', false);
        $this->toggle('remove', false);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->allowEmptyString('email')
            ->add('email', 'validEmailCustom', [
                'rule' => ['checkEmailValidation'],
                'message' => 'Please enter a valid email',
                'on' => function ($context) {
                    return !empty($context['data']['email']);
                }
            ])
            ->allowEmptyString('mobile_number')
            ->add('mobile_number', 'numeric', [
                'rule' => 'numeric',
                'message' => 'Only numbers are allowed'
            ])
            ->allowEmpty('photo_content');
    }

    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->isDirty('mobile_number') && !empty($entity->mobile_number)) {
            $conditions = ['mobile_number' => $entity->mobile_number, $this->aliasField($this->getPrimaryKey()) . ' !=' => $entity->id];
            if ($this->exists($conditions)) {
                $entity->unset('mobile_number');
            }
        }
    }

    public function viewBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['Genders', 'MainNationalities']);
    }

    public function viewBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        // Explicit order for every Information-section field so it matches the Directory >
        // Directories view template exactly (Photo, OpenEMIS ID, DOB, Guardian/Guardian Relation -
        // specific to this page, First/Middle/Third/Last/Preferred Name, Gender, Email, Mobile).
        // Leaving these to User.User's own defaults produced a different, inconsistent order in
        // practice - addRelationFields() below still owns 3/4 for guardian_name/guardian_relation_name.
        $this->field('photo_content', ['type' => 'image']);
        $this->field('openemis_no', ['type' => 'readonly']);
        $this->field('first_name');
        $this->field('middle_name');
        $this->field('third_name');
        $this->field('last_name');
        $this->field('preferred_name');
        $this->field('gender_id');
        $this->field('email');
         $this->field('date_of_birth', ['type' => 'readonly']);
        $this->field('mobile_number');
        
    }

    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupTabElements();
        $this->addRelationFields($entity);
        $this->fixIdentitiesDetailsOwner($entity);
        unset($extra['toolbarButtons']['export']);
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        $url = [
            'plugin' => 'Student',
            'controller' => 'Students',
            'action' => 'Guardians',
            '0' => 'index',
            '1' => $encodedQueryString,
        ];
        $toolbarButtonsArray['back'] = $this->getButtonTemplate();
        $toolbarButtonsArray['back']['label'] = '<i class="fa kd-back"></i>';
        $toolbarButtonsArray['back']['attr']['title'] = __('Back');
        $toolbarButtonsArray['back']['url'] = $url;
        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
       
    }

    private function fixIdentitiesDetailsOwner(Entity $entity)
    {
        if (isset($this->fields['details'])) {
            $this->field('details', ['data' => $this->getViewUserIdentities($entity->id)]);
        }
    }

    public function editBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['Genders', 'MainNationalities']);
    }

    public function editBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('photo_content', ['order' => 0]);
        $this->field('openemis_no', ['type' => 'readonly', 'order' => 1]);
        $this->field('date_of_birth', ['order' => 2]);
        $this->field('first_name', ['order' => 5]);
        $this->field('middle_name', ['order' => 6]);
        $this->field('third_name', ['order' => 7]);
        $this->field('last_name', ['order' => 8]);
        $this->field('preferred_name', ['order' => 9]);
        $this->field('gender_id', ['order' => 10]);
        $this->field('email', ['order' => 11]);
        $this->field('mobile_number', ['order' => 12]);
        $this->field('username', ['visible' => false]);
    }

    public function editAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupTabElements();
    }

    private function addRelationFields(Entity $entity)
    {
        $studentId = $this->getQueryString('student_id');
        $relationName = '';
        if (!empty($studentId)) {
            $relation = TableRegistry::getTableLocator()->get('Student.Guardians')
                ->find()
                ->where(['student_id' => $studentId, 'guardian_id' => $entity->id])
                ->contain(['GuardianRelations'])
                ->first();
            if ($relation && $relation->has('guardian_relation')) {
                $relationName = $relation->guardian_relation->name;
            }
        }

        $this->field('guardian_name', [
            'type' => 'readonly',
            'order' => 3,
            'attr' => ['label' => __('Guardian'), 'value' => $entity->name],
        ]);
        $this->field('guardian_relation_name', [
            'type' => 'readonly',
            'order' => 4,
            'attr' => ['label' => __('Guardian Relation'), 'value' => $relationName],
        ]);
    }

    private function setupTabElements()
    {
        $id = $this->request->getQuery('id') ?? 0;
        $userId = $this->request->getQuery('user_id') ?? 0;
        $options = [
            'userRole' => 'Student',
            'action' => $this->action,
            'id' => $id,
            'userId' => $userId,
        ];
        $tabElements = $this->setUserTabElements($options);
        $tabElements = $this->controller->TabPermission->checkTabPermission($tabElements);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'Guardians');
    }


}
