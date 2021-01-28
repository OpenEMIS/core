<?php
namespace Profile\Model\Table;

use ArrayObject;
use Cake\I18n\Time;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Utility\Text;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;
use Cake\Core\Configure;

class GuardiansTable extends ControllerActionTable
{
    private $editButtonAction = 'GuardianUser';

    public function initialize(array $config)
    {
        $this->table('student_guardians');
        parent::initialize($config);

        $this->belongsTo('StudentUser', ['className' => 'Institution.StudentUser', 'foreignKey' => 'student_id']);
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'guardian_id']);
        $this->belongsTo('GuardianRelations', ['className' => 'Student.GuardianRelations', 'foreignKey' => 'guardian_relation_id']);

        // to handle field type (autocomplete)
        $this->addBehavior('OpenEmis.Autocomplete');
        $this->addBehavior('User.User');
        $this->addBehavior('User.AdvancedNameSearch');
        if (!in_array('Risks', (array)Configure::read('School.excludedPlugins'))) {
            $this->addBehavior('Risk.Risks');
        }
        $this->addBehavior('ControllerAction.Image');
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('guardian_id', 'ruleStudentGuardianId', [
                'rule' => ['studentGuardianId'],
                'on' => 'create'
            ])
        ;
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.ajaxUserAutocomplete'] = 'ajaxUserAutocomplete';
        return $events;
    }

    public function afterAction(Event $event, $data)
    {
        $this->setFieldOrder([
            'photo_content', 'openemis_no', 'guardian_id', 'guardian_relation_id'
        ]);
    }

    public function onGetGuardianId(Event $event, Entity $entity)
    {
        if ($entity->has('_matchingData')) {
            return $entity->_matchingData['Users']->name;
        }
    }

    public function beforeAction(Event $event)
    {
        if ($this->controller->name == 'Directories') {
            $studentId = $this->Session->read('Directory.Directories.id');
        } else if ($this->controller->name == 'Profiles') {
            $studentId = $this->Session->read('Auth.User.id');
        } else {
            $studentId = $this->Session->read('Student.Students.id');
        }

        $this->field('student_id', ['type' => 'hidden', 'value' => $studentId]);
        $this->field('guardian_id');
        $this->field('guardian_relation_id', ['type' => 'select']);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        //POCOR-5881 starts
        $studentId = '';
        if ($this->controller->name == 'Profiles') {
            $studentId = $this->Session->read('Auth.User.id');
        }
        //POCOR-5881 ends
        $search = $this->getSearchKey();
        if (!empty($search)) {
            // function from AdvancedNameSearchBehavior
            $query = $this->addSearchConditions($query, ['alias' => 'Users', 'searchTerm' => $search]);
        }
        //POCOR-5881 starts
        if(!empty($studentId)){
            $query->where(['Guardians.student_id'=>$studentId]);
        }
        //POCOR-5881 ends
        if (!isset($this->request->query['sort'])) {
            $orders = [
                $this->Users->aliasField('first_name'),
                $this->Users->aliasField('last_name')
            ];

            $query->order($orders);
        }
    }

    public function addAfterPatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $errors = $entity->errors();
        if (!empty($errors)) {
            $entity->unsetProperty('guardian_id');
            unset($data[$this->alias()]['guardian_id']);
        }
    }

    public function addAfterAction(Event $event, Entity $entity)
    {
        $this->field('id', ['value' => Text::uuid()]);
    }

    public function viewBeforeAction(Event $event)
    {
        $this->field('photo_content', ['type' => 'image', 'order' => 0]);
        $this->field('openemis_no', ['type' => 'readonly', 'order' => 1]);
    }

    public function editBeforeQuery(Event $event, Query $query)
    {
        $query->contain(['StudentUser', 'Users']);
    }

    public function editAfterAction(Event $event, Entity $entity)
    {
        $this->field('guardian_id', [
            'type' => 'readonly',
            'order' => 10,
            'attr' => ['value' => $entity->user->name_with_id]
        ]);
    }

    public function onUpdateFieldGuardianId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $attr['type'] = 'autocomplete';
            $attr['target'] = ['key' => 'guardian_id', 'name' => $this->aliasField('guardian_id')];
            $attr['noResults'] = __('No Guardian found.');
            $attr['attr'] = ['placeholder' => __('OpenEMIS ID, Identity Number or Name')];
            $action = 'Guardians';
            if ($this->controller->name == 'Profiles') {
                $action = 'ProfileGuardians';
            }
            $attr['url'] = ['controller' => $this->controller->name, 'action' => $action, 'ajaxUserAutocomplete'];

            $iconSave = '<i class="fa fa-check"></i> ' . __('Save');
            $iconAdd = '<i class="fa kd-add"></i> ' . __('Create New');
            $attr['onNoResults'] = "$('.btn-save').html('" . $iconAdd . "').val('new')";
            $attr['onBeforeSearch'] = "$('.btn-save').html('" . $iconSave . "').val('save')";
        } else if ($action == 'index') {
            $attr['sort'] = ['field' => 'Users.first_name'];
        }
        return $attr;
    }

    public function addOnInitialize(Event $event, Entity $entity)
    {
        $this->Session->delete('Student.Guardians.new');
    }

    public function addOnNew(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $options['validate']=true;
        $patch = $this->patchEntity($entity, $data->getArrayCopy(), $options->getArrayCopy());
        $errorCount = count($patch->errors());

        if ($errorCount == 0 || ($errorCount == 1 && array_key_exists('guardian_id', $patch->errors()))) {
            $this->Session->write('Student.Guardians.new', $data[$this->alias()]);
            $event->stopPropagation();

            $action = ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name, 'action' => 'GuardianUser', 'add'];
            if ($this->controller->name == 'Profiles') {
                $guardianRelationId = $entity->guardian_relation_id;
                $action = ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name, 'action' => 'ProfileGuardianUser', 'add', $this->paramsEncode(['guardian_relation_id' => $guardianRelationId])];
            }
            return $this->controller->redirect($action);
        } else {
            $this->Alert->error('general.add.failed', ['reset' => true]);
        }
    }

    public function ajaxUserAutocomplete()
    {
        $this->controller->autoRender = false;
        $this->ControllerAction->autoRender = false;

        if ($this->request->is(['ajax'])) {
            $term = $this->request->query['term'];
            // only search for guardian
            $query = $this->Users->find()
                ->select([
                    $this->Users->aliasField('openemis_no'),
                    $this->Users->aliasField('first_name'),
                    $this->Users->aliasField('middle_name'),
                    $this->Users->aliasField('third_name'),
                    $this->Users->aliasField('last_name'),
                    $this->Users->aliasField('preferred_name'),
                    $this->Users->aliasField('id')
                ])
                ->where([$this->Users->aliasField('is_guardian') => 1])->limit(100);

            $term = trim($term);
            if (!empty($term)) {
                $query = $this->addSearchConditions($query, ['alias' => 'Users', 'searchTerm' => $term]);
            }

            $list = $query->all();

            $data = [];
            foreach ($list as $obj) {
                $label = sprintf('%s - %s', $obj->openemis_no, $obj->name);
                $data[] = ['label' => $label, 'value' => $obj->id];
            }

            echo json_encode($data);
            die;
        }
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

        $urlButtons = [
            'plugin' => $this->controller->plugin,
            'controller' => $this->controller->name,
            'action' => 'ProfileGuardianUser'
        ];

        if (array_key_exists('view', $buttons) && $entity->has('_matchingData')) {
            $buttons['view']['url'] = $urlButtons;
            $buttons['view']['url'][0] = 'view';
            $buttons['view']['url'][1] = $this->paramsEncode(['id' =>  $entity->_matchingData['Users']->id, 'ProfileGuardians.id' => $entity->id]);
        }

        if (array_key_exists('edit', $buttons) && $entity->has('_matchingData')) {
            $buttons['edit']['url'] = $urlButtons;
            $buttons['edit']['url'][0] = 'edit';
            $buttons['edit']['url'][1] = $this->paramsEncode(['id' =>  $entity->_matchingData['Users']->id, 'ProfileGuardians.id' => $entity->id]);
        }

        return $buttons;
    }

    public function editButtonAction($action = null)
    {
        if (is_null($action)) {
            return $this->editButtonAction;
        }
        $this->editButtonAction = $action;
    }
}
