<?php
namespace Student\Model\Table;

use ArrayObject;
use Cake\I18n\Time;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Utility\Text;
use Cake\Validation\Validator;
use Cake\Core\Configure;
use App\Model\Table\ControllerActionTable;

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

    private function setupTabElements($entity = null)
    {
        if ($this->controller->name == 'Scholarships') {
            $tabElements = $this->ScholarshipTabs->getScholarshipApplicationTabs();
        } else {
            if ($this->action != 'view') {
                if ($this->controller->name == 'Directories') {
                    $options['type'] = 'student';
                    $tabElements = $this->controller->getStudentGuardianTabElements($options);
                } else {
                    $tabElements = $this->controller->getUserTabElements();
                }
            } elseif ($this->action == 'view') {
                if ($this->controller->name == 'Directories') {
                    $tabElements = $this->controller->getUserTabElements(['entity' => $entity, 'id' => $entity->guardian_id, 'userRole' => 'Guardians']);
                } elseif ($this->controller->name == 'Students') {
                    $tabElements = $this->controller->getGuardianTabElements(['entity' => $entity, 'id' => $entity->guardian_id, 'userRole' => 'Guardians']);
                }
            }
        }

        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $listeners = [
            TableRegistry::get('Student.GuardianUser')
        ];
        $this->dispatchEventToModels('Model.Guardian.afterSave', [$entity], $this, $listeners);
    }

    public function afterAction(Event $event, $data)
    {
        if ($this->action != 'view') {
            $this->setupTabElements();
        }

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
        } else {
            $studentId = $this->Session->read('Student.Students.id');
        }
        $this->field('student_id', ['type' => 'hidden', 'value' => $studentId]);
        $this->field('guardian_id');
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $search = $this->getSearchKey();
        if (!empty($search)) {
            // function from AdvancedNameSearchBehavior
            $query = $this->addSearchConditions($query, ['alias' => 'Users', 'searchTerm' => $search]);
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
        $this->field('guardian_relation_id', [
            'type' => 'select',
            'entity' => $entity
        ]);
    }

    public function viewBeforeAction(Event $event)
    {
        $this->field('photo_content', ['type' => 'image', 'order' => 0]);
        $this->field('openemis_no', ['type' => 'readonly', 'order' => 1]);
    }

    public function viewAfterAction(Event $event, Entity $entity)
    {
        $this->setupTabElements($entity);
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
        $this->field('guardian_relation_id', [
            'type' => 'select',
            'entity' => $entity
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
            if ($this->controller->name == 'Directories') {
                $action = 'StudentGuardians';
            }
            $attr['url'] = ['controller' => $this->controller->name, 'action' => $action, 'ajaxUserAutocomplete'];

            $requestData = $this->request->data;
            if (isset($requestData) && !empty($requestData[$this->alias()]['guardian_id'])) {
                $guardianId = $requestData[$this->alias()]['guardian_id'];
                $guardianName = $this->Users->get($guardianId)->name_with_id;

                $attr['attr']['value'] = $guardianName;
            }

            $iconSave = '<i class="fa fa-check"></i> ' . __('Save');
            $iconAdd = '<i class="fa kd-add"></i> ' . __('Create New');
            $attr['onNoResults'] = "$('.btn-save').html('" . $iconAdd . "').val('new')";
            $attr['onBeforeSearch'] = "$('.btn-save').html('" . $iconSave . "').val('save')";
            $attr['onSelect'] = "$('#reload').click();";
        } elseif ($action == 'index') {
            $attr['sort'] = ['field' => 'Guardians.first_name'];
        }
        return $attr;
    }

    public function onUpdateFieldGuardianRelationId(Event $event, array $attr, $action, Request $request) 
    {
        if ($action == 'add' || $action == 'edit') {
            $entity = $attr['entity'];
            $guardianGenderId = null;
            $guardianRelationOptions = [];

            if ($entity->has('guardian_id')) {
                $guardianGenderId = $this->Users->get($entity->guardian_id)->gender_id;
            }

            $guardianRelationOptions = $this->GuardianRelations->getAvailableGuardianRelations($guardianGenderId);
            $attr['options'] = $guardianRelationOptions;
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
            if ($this->controller->name == 'Directories') {
                $action = ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name, 'action' => 'StudentGuardianUser', 'add'];
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

            $UserIdentitiesTable = TableRegistry::get('User.Identities');

            $query = $this->Users
                ->find()
                ->select([
                    $this->Users->aliasField('openemis_no'),
                    $this->Users->aliasField('first_name'),
                    $this->Users->aliasField('middle_name'),
                    $this->Users->aliasField('third_name'),
                    $this->Users->aliasField('last_name'),
                    $this->Users->aliasField('preferred_name'),
                    $this->Users->aliasField('id')
                ])
                ->leftJoin(
                    [$UserIdentitiesTable->alias() => $UserIdentitiesTable->table()],
                    [
                        $UserIdentitiesTable->aliasField('security_user_id') . ' = ' . $this->Users->aliasField('id')
                    ]
                )
                ->group([
                    $this->Users->aliasField('id')
                ])
                ->limit(100);

            $term = trim($term);

            if (!empty($term)) {
                $query = $this->addSearchConditions($query, ['alias' => 'Users', 'searchTerm' => $term, 'OR' => ['`Identities`.number LIKE ' => $term . '%']]);
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

        $newButtons = [];
        if (array_key_exists('view', $buttons)) {
            $newButtons['view'] = $buttons['view'];
        }

        if (array_key_exists('edit', $buttons)) {
            $editProfile = $buttons['edit'];
            $editRelation = $buttons['edit'];

            $editProfile['label'] = '<i class="fa fa-pencil"></i>' . __('Edit Profile');
            $editRelation['label'] = '<i class="fa fa-pencil"></i>' . __('Edit Relation');

            $newButtons['editProfile'] = $editProfile;
            $newButtons['editRelation'] = $editRelation;
            $newButtons['editProfile']['url'] = [
                'plugin' => $this->controller->plugin,
                'controller' => $this->controller->name,
                'action' => $this->editButtonAction(),
                'edit',
                $this->paramsEncode(['id' =>  $entity->_matchingData['Users']->id, 'StudentGuardians.id' => $entity->id])
            ];
        }

        if (array_key_exists('remove', $buttons)) {
            $newButtons['remove'] = $buttons['remove'];
        }

        return $newButtons;
    }

    public function editButtonAction($action = null)
    {
        if (is_null($action)) {
            return $this->editButtonAction;
        }
        $this->editButtonAction = $action;
    }
}
