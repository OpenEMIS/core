<?php
namespace Examination\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class RegistrationDirectoryTable extends ControllerActionTable {
    public function initialize(array $config)
    {
        $this->table('security_users');
        $this->entityClass('User.User');
        parent::initialize($config);

        $this->belongsTo('Genders', ['className' => 'User.Genders']);
        $this->belongsTo('AddressAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'address_area_id']);
        $this->belongsTo('BirthplaceAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'birthplace_area_id']);

        $this->addBehavior('User.User');
        $this->addBehavior('OpenEmis.Section');

        // $this->toggle('add', false);
        $this->toggle('edit', false);
        // $this->toggle('view', false);
        $this->toggle('remove', false);
    }

    // public function validationDefault(Validator $validator) {
    //     $validator = parent::validationDefault($validator);

    //     return $validator
    //         ->add('text_value', 'ruleUnique', [
    //             'rule' => ['validateUnique', ['scope' => ['examination_id', 'student_id']]],
    //             'provider' => 'table',
    //             // 'message' => __('This field has to be unique'),
    //             // 'on' => function ($context) {
    //             //     if (array_key_exists('unique', $context['data'])) {
    //             //         return $context['data']['unique'];
    //             //     }
    //             // }
    //         ]);
    // }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->toggle('add', false);

        $this->field('first_name', ['visible' => false]);
        $this->field('middle_name', ['visible' => false]);
        $this->field('third_name', ['visible' => false]);
        $this->field('last_name', ['visible' => false]);
        $this->field('preferred_name', ['visible' => false]);
        $this->field('gender_id', ['visible' => false]);
        $this->field('date_of_birth', ['visible' => false]);
        $this->field('identity_number', ['visible' => false]);
        $this->field('address', ['visible' => false]);
        $this->field('postal_code', ['visible' => false]);
        $this->field('address_area_id', ['visible' => false]);
        $this->field('birthplace_area_id', ['visible' => false]);

        $this->field('institution');
        $this->field('student_status');
        // $this->fields['student_status']['order'] = 5;

        // $this->field('student_status', ['after' => 'name']);
        // $this->setFieldOrder('photo_content', 'openemis_no', 'name', 'student_status');

        // back button direct to Registered Students
        $backBtn['type'] = 'button';
        $backBtn['label'] = '<i class="fa kd-back"></i>';
        $backBtn['attr'] = [
            'class' => 'btn btn-xs btn-default',
            'data-toggle' => 'tooltip',
            'data-placement' => 'bottom',
            'escape' => false,
            'title' => 'Back'
        ];
        $backBtn['url']= [
            'plugin' => 'Examination',
            'controller' => 'Examinations',
            'action' => 'RegisteredStudents',
            '0' => 'index'
        ];
        $extra['toolbarButtons']['back'] = $backBtn;
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->where([$this->aliasField('is_student') => 1]);

        $search = $this->getSearchKey();
        if (!empty($search)) {
            // function from AdvancedNameSearchBehavior
            $query = $this->addSearchConditions($query, ['searchTerm' => $search]);
        }
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        // add button direct to register students
        $addBtn['type'] = 'button';
        $addBtn['label'] = '<i class="fa kd-add"></i>';
        $addBtn['attr'] = [
            'class' => 'btn btn-xs btn-default',
            'data-toggle' => 'tooltip',
            'data-placement' => 'bottom',
            'escape' => false,
            'title' => 'Register'
        ];
        $params = [
            'plugin' => 'Examination',
            'controller' => 'Examinations',
            'action' => 'RegisteredStudents',
            '0' => 'add'
        ];
        $addBtn['url'] = $this->ControllerAction->setQueryString($params, ['user_id' => $entity->id]);
        $extra['toolbarButtons']['add'] = $addBtn;
    }

    public function onGetInstitution(Event $event, Entity $entity)
    {
        $userId = $entity->id;

        $studentInstitutions = [];
        $InstitutionStudentTable = TableRegistry::get('Institution.Students');
        $studentInstitutions = $InstitutionStudentTable->find()
            ->matching('StudentStatuses')
            ->matching('Institutions')
            ->where([
                $InstitutionStudentTable->aliasField('student_id') => $userId,
            ])
            ->distinct(['id'])
            ->select(['id' => $InstitutionStudentTable->aliasField('institution_id'), 'name' => 'Institutions.name', 'student_status_name' => 'StudentStatuses.name'])
            ->order(['(CASE WHEN '.$InstitutionStudentTable->aliasField('modified').' IS NOT NULL THEN '.$InstitutionStudentTable->aliasField('modified').' ELSE '.
            $InstitutionStudentTable->aliasField('created').' END) DESC'])
            ->first();

        $value = '';
        $name = '';
        if (!empty($studentInstitutions)) {
            $value = $studentInstitutions->student_status_name;
            $name = $studentInstitutions->name;
        }
        $entity->student_status_name = $value;

        return $name;
    }

    public function onGetStudentStatus(Event $event, Entity $entity)
    {
        return __($entity->student_status_name);
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

        $params = [
            'plugin' => 'Examination',
            'controller' => 'Examinations',
            'action' => 'RegisteredStudents',
            '0' => 'add'
        ];
        $url = $this->ControllerAction->setQueryString($params, ['user_id' => $entity->id]);

        $buttons['add'] = [
            'label' => '<i class="fa kd-add"></i>'.__('Register'),
            'attr' => $buttons['view']['attr'],
            'url' => $url
        ];

        return $buttons;
    }
}
