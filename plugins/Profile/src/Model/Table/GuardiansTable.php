<?php
namespace Profile\Model\Table;

use ArrayObject;
use Cake\I18n\Time;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Http\ServerRequest;
use Cake\Utility\Text;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;
use Cake\Core\Configure;

class GuardiansTable extends ControllerActionTable
{
    private $editButtonAction = 'GuardianUser';

    public function initialize(array $config): void
    {
        $this->setTable('student_guardians');
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

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('guardian_id', 'ruleStudentGuardianId', [
                'rule' => ['studentGuardianId'],
                'on' => 'create'
            ])
        ;
    }

    public function implementedEvents(): array
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
        if ($this->controller->getName() == 'Directories') {
            $studentId = $this->Session->read('Directory.Directories.id');
        } else if ($this->controller->getName() == 'Profiles') {
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

        if ($this->controller->getName() == 'Profiles') {
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
        if (is_null($this->request->getQuery('sort'))) {
            $orders = [
                $this->Users->aliasField('first_name'),
                $this->Users->aliasField('last_name')
            ];

            $query->order($orders);
        }
    }

    public function addAfterPatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $errors = $entity->getErrors();
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
            //'type' => 'readonly',
            'order' => 10,
            'attr' => ['value' => $entity->user->name_with_id]
        ]);
    }

    public function onUpdateFieldGuardianId(Event $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $attr['type'] = 'autocomplete';
            $attr['target'] = ['key' => 'guardian_id', 'name' => $this->aliasField('guardian_id')];
            $attr['noResults'] = __('No Guardian found.');
            $attr['attr'] = ['placeholder' => __('OpenEMIS ID, Identity Number or Name')];
            $action = 'Guardians';
            if ($this->controller->getName() == 'Profiles') {
                $action = 'ProfileGuardians';
            }
            $attr['url'] = ['controller' => $this->controller->getName(), 'action' => $action, 'ajaxUserAutocomplete'];

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
        $errorCount = count($patch->getErrors());

        if ($errorCount == 0 || ($errorCount == 1 && array_key_exists('guardian_id', $patch->getErrors()))) {
            $this->Session->write('Student.Guardians.new', $data[$this->getAlias()]);
            $event->stopPropagation();

            $action = ['plugin' => $this->controller->getPlugin(), 'controller' => $this->controller->getName(), 'action' => 'GuardianUser', 'add'];
            if ($this->controller->getName() == 'Profiles') {
                $guardianRelationId = $entity->guardian_relation_id;
                $action = ['plugin' => $this->controller->getPlugin(), 'controller' => $this->controller->getName(), 'action' => 'ProfileGuardianUser', 'add', $this->paramsEncode(['guardian_relation_id' => $guardianRelationId])];
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
            $term = $this->request->getQuery('term');
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
            'plugin' => $this->controller->getPlugin(),
            'controller' => $this->controller->getName(),
            'action' => 'ProfileGuardianUser'
        ];

        if (isset($buttons['view']) && $entity->has('_matchingData')) {
            $buttons['view']['url'] = $urlButtons;
            $buttons['view']['url'][0] = 'view';
            $buttons['view']['url'][1] = $this->paramsEncode(['id' =>  $entity->_matchingData['Users']->id, 'ProfileGuardians.id' => $entity->id]);
        }

        if (isset($buttons['edit']) && $entity->has('_matchingData')) {
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

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'guardian_id') {
            return __('Guardian');
        } elseif ($field == 'guardian_relation_id') {
            return __(' Guardian Relations');
        } elseif ($field == 'account_name') {
            return __('Account Name');
        } elseif ($field == 'account_number') {
            return __('Account Number');
        } elseif ($field == 'active') {
            return __('Active');
        } elseif ($field == 'validation_rule') {
            return __('Validation Rule');
        } elseif ($field == 'modified_user_id') {
            return __('Modified By');
        } elseif ($field == 'modified') {
            return __('Modified On');
        } elseif ($field == 'created_user_id') {
            return __('Created By');
        } elseif ($field == 'created') {
            return __('Created On');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}
