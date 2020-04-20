<?php
namespace Examination\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\ORM\ResultSet;
use Cake\Network\Request;
use Cake\Controller\Component;
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
        $this->belongsTo('MainNationalities', ['className' => 'FieldOption.Nationalities', 'foreignKey' => 'nationality_id']);
        $this->belongsTo('MainIdentityTypes', ['className' => 'FieldOption.IdentityTypes', 'foreignKey' => 'identity_type_id']);

        $this->hasMany('SpecialNeeds', ['className' => 'SpecialNeeds.SpecialNeedsAssessments', 'foreignKey' => 'security_user_id']);
        
        $this->addBehavior('User.User');
        $this->addBehavior('User.AdvancedNameSearch');

        $this->toggle('edit', false);
        $this->toggle('remove', false);
    }

    public function implementedEvents() {
        $events = parent::implementedEvents();
        $events['Model.Navigation.breadcrumb'] = 'onGetBreadcrumb';
        return $events;
    }

    public function onGetBreadcrumb(Event $event, Request $request, Component $Navigation, $persona)
    {
        $indexUrl = ['plugin' => 'Examination', 'controller' => 'Examinations', 'action' => 'RegisteredStudents'];
        $Navigation->substituteCrumb('Examination', 'Examination', $indexUrl);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['SpecialNeeds.SpecialNeedsTypes'])
            ->where([$this->aliasField('super_admin') => 0]);

        $search = $this->getSearchKey();
        if (!empty($search)) {
            // function from AdvancedNameSearchBehavior
            $query = $this->addSearchConditions($query, ['searchTerm' => $search]);
        }
    }

    public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $this->toggle('add', false);

        $this->field('special_need');
        $this->field('first_name', ['visible' => false]);
        $this->field('middle_name', ['visible' => false]);
        $this->field('third_name', ['visible' => false]);
        $this->field('last_name', ['visible' => false]);
        $this->field('preferred_name', ['visible' => false]);
        $this->field('identity_number', ['visible' => false]);
        $this->field('address', ['visible' => false]);
        $this->field('postal_code', ['visible' => false]);
        $this->field('address_area_id', ['visible' => false]);
        $this->field('birthplace_area_id', ['visible' => false]);
        $this->field('photo_content', ['visible' => false]);

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

    public function afterAction(Event $event, ArrayObject $extra)
    {
        if ($this->action == 'index') {
            $this->setFieldOrder(['openemis_no', 'name', 'date_of_birth', 'gender', 'special_need']);
        }
    }

    public function viewBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['SpecialNeeds.SpecialNeedsTypes', 'SpecialNeeds.SpecialNeedDifficulties']);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        // add button direct to register students add
        $addBtn['type'] = 'button';
        $addBtn['label'] = '<i class="fa kd-add"></i>';
        $addBtn['attr'] = [
            'class' => 'btn btn-xs btn-default',
            'data-toggle' => 'tooltip',
            'data-placement' => 'bottom',
            'escape' => false,
            'title' => __('Register')
        ];
        $params = [
            'plugin' => 'Examination',
            'controller' => 'Examinations',
            'action' => 'RegisteredStudents',
            '0' => 'add'
        ];
        $addBtn['url'] = $this->ControllerAction->setQueryString($params, ['user_id' => $entity->id]);
        $extra['toolbarButtons']['add'] = $addBtn;

        $this->field('special_needs', ['after' => 'identity_number', 'type' => 'custom_special_needs']);
    }

    public function onGetDateOfBirth(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('date_of_birth')) {
            $value = $entity->date_of_birth;
        }
        return $value;
    }

    public function onGetGenderId(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('gender')) {
            $value = $entity->gender->name;
        }

        return $value;
    }

    public function onGetSpecialNeed(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('special_needs') && !empty($entity->special_needs)) {
            $specialNeeds = $entity->special_needs;

            foreach ($specialNeeds as $key => $need) {
                $array[] = $need->special_needs_type->name;
            }
            $value = implode(', ', $array);
        }

        return $value;
    }

    public function onGetCustomSpecialNeedsElement(Event $event, $action, $entity, $attr, $options=[])
    {
        if ($action == 'view') {
            $needsArray = [];
            if ($entity->has('special_needs') && !empty($entity->special_needs)) {
                $specialNeeds = $entity->special_needs;

                foreach ($specialNeeds as $key => $need) {
                    $needsArray[] = ['special_need' => $need->special_needs_type->name, 'special_need_difficulty' => $need->special_need_difficulty->name];
                }
            }

            $attr['data'] = $needsArray;
        }

        return $event->subject()->renderElement('Examination.special_needs', ['attr' => $attr]);
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
