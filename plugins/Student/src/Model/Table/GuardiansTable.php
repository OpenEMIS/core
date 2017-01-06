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
use App\Model\Table\AppTable;

class GuardiansTable extends AppTable {
    private $editButtonAction = 'GuardianUser';

    public function initialize(array $config) {
        $this->table('student_guardians');
        parent::initialize($config);

        $this->belongsTo('Students',            ['className' => 'Institution.StudentUser', 'foreignKey' => 'student_id']);
        $this->belongsTo('Users',               ['className' => 'Security.Users', 'foreignKey' => 'guardian_id']);
        $this->belongsTo('GuardianRelations',   ['className' => 'Student.GuardianRelations', 'foreignKey' => 'guardian_relation_id']);

        // to handle field type (autocomplete)
        $this->addBehavior('OpenEmis.Autocomplete');
        $this->addBehavior('User.User');
        $this->addBehavior('User.AdvancedNameSearch');
        $this->addBehavior('Indexes.Indexes');
    }

    // public function implementedEvents()
    // {
    //     $events = parent::implementedEvents();
    //     $events['Model.InstitutionStudentIndexes.calculateIndexValue'] = 'institutionStudentIndexCalculateIndexValue';
    //     return $events;
    // }

    public function validationDefault(Validator $validator) {
	$validator = parent::validationDefault($validator);

        return $validator
            ->add('guardian_id', 'ruleStudentGuardianId', [
                'rule' => ['studentGuardianId'],
                'on' => 'create'
            ])
        ;
    }

    private function setupTabElements($entity=null) {
        if ($this->action != 'view') {
            if ($this->controller->name == 'Directories') {
                $options['type'] = 'student';
                $tabElements = $this->controller->getStudentGuardianTabElements($options);
            } else {
                $tabElements = $this->controller->getUserTabElements();
            }
            $this->controller->set('tabElements', $tabElements);
            $this->controller->set('selectedAction', $this->alias());
        } elseif ($this->action == 'view') {
            $url = ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name];

            $tabElements = [
                'Guardians' => ['text' => __('Relation')],
                'GuardianUser' => ['text' => __('General')]
            ];
            $action = $this->alias();
            $actionUser = 'GuardianUser';
            if ($this->controller->name == 'Directories') {
                $action = 'StudentGuardians';
                $actionUser = 'StudentGuardianUser';
            }
            $tabElements['Guardians']['url'] = array_merge($url, ['action' => $action, 'view', $entity->id]);
            $tabElements['GuardianUser']['url'] = array_merge($url, ['action' => $actionUser, 'view', $entity->guardian_id, 'id' => $entity->id]);

            $this->controller->set('tabElements', $tabElements);
            $this->controller->set('selectedAction', $this->alias());
        }
    }

    public function afterAction(Event $event, $data) {
        if ($this->action != 'view') {
            $this->setupTabElements();
        }
    }

    public function onGetGuardianId(Event $event, Entity $entity) {
        if ($entity->has('_matchingData')) {
            return $entity->_matchingData['Users']->name;
        }
    }

    public function beforeAction(Event $event) {
        if ($this->controller->name == 'Directories') {
            $studentId = $this->Session->read('Directory.Directories.id');
        } else {
            $studentId = $this->Session->read('Student.Students.id');
        }
        $this->ControllerAction->field('student_id', ['type' => 'hidden', 'value' => $studentId]);
        $this->ControllerAction->field('guardian_id');
        $this->ControllerAction->field('guardian_relation_id', ['type' => 'select']);
    }

    public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
        $search = $this->ControllerAction->getSearchKey();
        if (!empty($search)) {
            // function from AdvancedNameSearchBehavior
            $query = $this->addSearchConditions($query, ['alias' => 'Users', 'searchTerm' => $search]);
        }
    }

    public function addAfterPatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
        $errors = $entity->errors();
        if (!empty($errors)) {
            $entity->unsetProperty('guardian_id');
            unset($data[$this->alias()]['guardian_id']);
        }
    }

    public function addAfterAction(Event $event, Entity $entity) {
        $this->ControllerAction->field('id', ['value' => Text::uuid()]);
    }

    public function viewBeforeAction(Event $event) {
        $this->ControllerAction->field('photo_content', ['type' => 'image', 'order' => 0]);
        $this->ControllerAction->field('openemis_no', ['type' => 'readonly', 'order' => 1]);
        $this->fields['guardian_id']['order'] = 10;
    }

    public function viewAfterAction(Event $event, Entity $entity) {
        $this->setupTabElements($entity);
    }

    public function editBeforeQuery(Event $event, Query $query) {
        $query->contain(['Students', 'Users']);
    }

    public function editAfterAction(Event $event, Entity $entity) {
        $this->ControllerAction->field('guardian_id', [
            'type' => 'readonly',
            'order' => 10,
            'attr' => ['value' => $entity->user->name_with_id]
        ]);
    }

    public function onUpdateFieldGuardianId(Event $event, array $attr, $action, Request $request) {
        if ($action == 'add') {
            $attr['type'] = 'autocomplete';
            $attr['target'] = ['key' => 'guardian_id', 'name' => $this->aliasField('guardian_id')];
            $attr['noResults'] = __('No Guardian found.');
            $attr['attr'] = ['placeholder' => __('OpenEMIS ID or Name')];
            $action = 'Guardians';
            if ($this->controller->name == 'Directories') {
                $action = 'StudentGuardians';
            }
            $attr['url'] = ['controller' => $this->controller->name, 'action' => $action, 'ajaxUserAutocomplete'];

            $iconSave = '<i class="fa fa-check"></i> ' . __('Save');
            $iconAdd = '<i class="fa kd-add"></i> ' . __('Create New');
            $attr['onNoResults'] = "$('.btn-save').html('" . $iconAdd . "').val('new')";
            $attr['onBeforeSearch'] = "$('.btn-save').html('" . $iconSave . "').val('save')";
        } else if ($action == 'index') {
            $attr['sort'] = ['field' => 'Guardians.first_name'];
        }
        return $attr;
    }

    public function addOnInitialize(Event $event, Entity $entity) {
        $this->Session->delete('Student.Guardians.new');
    }

    public function addOnNew(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
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

    public function ajaxUserAutocomplete() {
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
            foreach($list as $obj) {
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
                $entity->_matchingData['Users']->id,
                'id' => $entity->id
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

    // public function institutionStudentIndexCalculateIndexValue(Event $event, ArrayObject $params)
    // {
    //     $institutionId = $params['institution_id'];
    //     $studentId = $params['student_id'];
    //     $academicPeriodId = $params['academic_period_id'];

    //     $quantityResult = $this->find()
    //         ->where([$this->aliasField('student_id') => $studentId])
    //         ->all()->toArray();

    //     $quantity = !empty(count($quantityResult)) ? count($quantityResult) : 0;

    //     return $valueIndex = $quantity;
    // }

    // public function getReferenceDetails($institutionId, $studentId, $academicPeriodId, $threshold, $criteriaName)
    // {
    //     $guardianList = $this->find()
    //         ->contain(['Users', 'GuardianRelations'])
    //         ->where([$this->aliasField('student_id') => $studentId])
    //         ->all();

    //     $referenceDetails = [];
    //     foreach ($guardianList as $key => $obj) {
    //         $guardianName = $obj->user->first_name . ' ' . $obj->user->last_name;
    //         $guardianRelation = $obj->guardian_relation->name;

    //         $referenceDetails[$obj->guardian_id] = $guardianName . ' (' . __($guardianRelation) . ')';
    //     }

    //     // tooltip only receieved string to be display
    //     $reference = '';
    //     if (!empty($referenceDetails)) {
    //         foreach ($referenceDetails as $key => $referenceDetailsObj) {
    //             $reference = $reference . $referenceDetailsObj . ' <br/>';
    //         }
    //     } else {
    //         $reference = __('No Guardians');
    //     }

    //     return $reference;
    // }
}
