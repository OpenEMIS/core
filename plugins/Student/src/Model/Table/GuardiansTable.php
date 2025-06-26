<?php

namespace Student\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Http\ServerRequest;
use Cake\Utility\Text;
use Cake\Validation\Validator;
use Cake\Core\Configure;
use App\Model\Table\ControllerActionTable;
use Cake\ORM\Table;
use Cake\Log\Log;
use Cake\Utility\Inflector;

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
            ]);
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.ajaxUserAutocomplete'] = 'ajaxUserAutocomplete';
        // Starts POCOR-6592
        $events['ControllerAction.Model.ajaxUserStaffAutocomplete'] = 'ajaxUserStaffAutocomplete';
        // Ends  POCOR-6592
        $events['ControllerAction.Model.add.beforeAction'] = 'addDeleteBeforeAction';
        $events['ControllerAction.Model.delete.beforeAction'] = 'addDeleteBeforeAction';
        return $events;
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

    private function setupTabElements($entity = null)
    {
        if ($this->controller->getName() == 'Scholarships') {
            $tabElements = $this->ScholarshipTabs->getScholarshipApplicationTabs();
        } else {
            if ($this->action != 'view') {
                if ($this->controller->getName() == 'Directories') {
                    $options['type'] = 'student';
                    $tabElements = $this->controller->getStudentGuardianTabElements($options);
                } else {
                    //$tabElements = $this->controller->getUserTabElements();
                }
            } elseif ($this->action == 'view') {
                if ($this->controller->getName() == 'Directories') {
//                    $tabElements = $this->controller->getUserTabElements(['entity' => $entity, 'id' => $entity->guardian_id, 'userRole' => 'Guardians']);
                } elseif ($this->controller->getName() == 'Students') {
//                    $tabElements = $this->controller->getGuardianTabElements(['entity' => $entity, 'id' => $entity->guardian_id, 'userRole' => 'Guardians']);
                }
            }
        }

        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->getAlias());
    }

    public function onGetGuardianId(Event $event, Entity $entity)
    {
        if ($entity->has('_matchingData')) {
            return $entity->_matchingData['Users']->name;
        }
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {

        if ($this->controller->getName() == 'Directories') {
            // POCOR-8014-n
            $requestDataa = base64_decode($this->request->getQuery('queryString'));
            $requestDataa = json_decode($requestDataa, true);
            $studentId = $requestDataa['student_id'];
        } elseif ($this->controller->getName() == 'Guardians' || $this->controller->getName() == 'GuardianNavs') {
            $studentId = $this->Session->read('Auth.User.id');
        } elseif ($this->controller->getName() == 'Students' && isset($this->request->getParam('pass')[1])) {
            $studentId = $this->getQueryString('student_id');
        } else {
            //$studentId = $this->Session->read('Student.Students.id');
            //echo "<pre>"; print_r($this->getQueryString('security_user_id')); die;
            $studentId = $this->getQueryString('security_user_id');
        }

        $this->field('student_id', ['type' => 'hidden', 'value' => $studentId]);
        $this->field('guardian_id');

        // Start POCOR-5188
        if ($this->request->getParam('controller') == 'Students') {
            $is_manual_exist = $this->getManualUrl('Institutions', 'Guardian Languages', 'Students - Guardians');
            if (!empty($is_manual_exist)) {
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target' => '_blank'
                ];

                $helpBtn['url'] = $is_manual_exist['url'];
                $helpBtn['type'] = 'button';
                $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
                $helpBtn['attr'] = $btnAttr;
                $helpBtn['attr']['title'] = __('Help');
                $extra['toolbarButtons']['help'] = $helpBtn;
            }
        } elseif ($this->request->getParam('controller') == 'Directories') {
            $is_manual_exist = $this->getManualUrl('Directory', 'Guardian Relation', 'Students - Guardians');
            if (!empty($is_manual_exist)) {
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target' => '_blank'
                ];

                $helpBtn['url'] = $is_manual_exist['url'];
                $helpBtn['type'] = 'button';
                $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
                $helpBtn['attr'] = $btnAttr;
                $helpBtn['attr']['title'] = __('Help');
                $extra['toolbarButtons']['help'] = $helpBtn;
            }

        }
        // End POCOR-5188
        if (isset($extra['toolbarButtons']['back'])) {
            $toolbarButtons = $extra['toolbarButtons'];
            $queryString = $this->getQueryString();
            $encodedQueryString = $this->paramsEncode($queryString);
            $url = $toolbarButtons['back']['url'];
            $url['0'] = 'index';
            $url['1'] = $encodedQueryString;
            unset($url['?']);
            unset($url['queryString']);
            $extra['toolbarButtons']['back']['url'] = $url;
        }

    }

    public function addDeleteBeforeAction(Event $event, ArrayObject $extra)
    {
        $url = $this->url('index', 'PASS');
        $extra['redirect'] = $url;
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $queryString = $this->getQueryString('security_user_id');
        $search = $this->getSearchKey();

        // Add your custom WHERE condition here
        $query->where(['student_id' => $queryString]);

        if (!empty($search)) {
            // function from AdvancedNameSearchBehavior
            $query = $this->addSearchConditions($query, ['alias' => 'Users', 'searchTerm' => $search]);
        }
    }

    public function addAfterPatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $errors = $entity->getErrors();
        if (!empty($errors)) {
            $entity->unsetProperty('guardian_id');
            unset($data[$this->getAlias()]['guardian_id']);
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

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('photo_content', ['type' => 'image', 'order' => 0]);
        $this->field('openemis_no', ['type' => 'readonly', 'order' => 1]);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
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

    public function onUpdateFieldGuardianId(Event $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $params = $this->getQueryString();
            //POCOR-7093 starts
            $SecurityUsers = self::getDynamicTableInstance('security_users');

            if ($this->controller->getName() == 'Directories') {
                $security_user_id = $params['security_user_id'];
                $securityUserData = $SecurityUsers->find()
                    ->where([
                        $SecurityUsers->aliasField('id') => $security_user_id])
                    ->enableHydration(false)
                    ->first();
                $dataArray = ['institution_id' => 0, 'student_id' => $security_user_id, 'openemis_no' => $securityUserData['openemis_no']];
            } else {
                $security_user_id = $params['security_user_id'];
                $institution_id = $params['institution_id'];
                $securityUserData = $SecurityUsers->find()
                    ->where([
                        $SecurityUsers->aliasField('id') => $security_user_id])
                    ->enableHydration(false)
                    ->first();
                $dataArray = ['institutionId' => $institution_id, 'institution_id' => $institution_id, 'institution_student_id' => $security_user_id, 'student_id' => $security_user_id, 'openemis_no' => $securityUserData['openemis_no']];
            }

            if ($this->request->getParam('plugin') == 'Student') {
                $queryString = $this->paramsEncode($dataArray);
                $event->stopPropagation();
                return $this->controller->redirect(['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Addguardian', $queryString]);
            } else {
                $queryString = base64_encode(json_encode($dataArray));
                $event->stopPropagation();
                return $this->controller->redirect(['plugin' => 'Directory', 'controller' => 'Directories', 'action' => 'Addguardian', $queryString]);
            }

            /*
            Note:- Don't uncomment this, becuase client's wants to redirect the page on directory add gaurdian page. Kindly connect with Anubhav/Ehteram.
            $attr['type'] = 'autocomplete';
            $attr['target'] = ['key' => 'guardian_id', 'name' => $this->aliasField('guardian_id')];
            $attr['noResults'] = __('No Guardian found.');
            $attr['attr'] = ['placeholder' => __('OpenEMIS ID, Identity Number or Name')];
            $action = 'Guardians';
            if ($this->controller->getName() == 'Directories') {
                $action = 'StudentGuardians';
            }
            $attr['url'] = ['controller' => $this->controller->getName(), 'action' => $action, 'ajaxUserAutocomplete'];

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
            $attr['onSelect'] = "$('#reload').click();";*///POCOR-7093 ends
        } elseif ($action == 'index') {
            $attr['sort'] = ['field' => 'Guardians.first_name'];
        }
        return $attr;
    }

    /**
     * Get a dynamic table instance with all associations.
     *
     * @param string $tableName . POCOR-8231
     * @return \Cake\ORM\Table
     * @author Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private static function getDynamicTableInstance(string $tableName): Table
    {
        // Parse plugin and table names if dot notation is used
        // Create a TableLocator instance
        $locator = TableRegistry::getTableLocator();

        try {
            // Try to get the table instance directly
            return $locator->get($tableName);
        } catch (\Exception $e) {
            Log::debug('Error: ' . $e->getMessage());
        }

        $parts = explode('.', $tableName);
        $plugin = count($parts) > 1 ? $parts[0] : null;
        $table = count($parts) > 1 ? $parts[1] : $parts[0];

        // Convert the table name to camel case as expected by CakePHP conventions
        $tableFullAlias = Inflector::camelize($tableName);
        $tableAlias = Inflector::camelize($table);

        // Create the fully qualified class name if a plugin is specified
        if ($plugin) {
            $className = $plugin . '\\Model\\Table\\' . $tableAlias . 'Table';
        } else {
            $className = 'App\\Model\\Table\\' . $tableAlias . 'Table';
        }

        // Check if the table instance already exists
        if (!$locator->exists($tableFullAlias)) {
            // Check if the specific table class exists
            if (!class_exists($className)) {
                $className = Table::class; // Fallback to generic Table class
            }

            // Configure a new table instance
            $locator->setConfig($tableAlias, [
                'className' => $className,
                'table' => $table,
                'alias' => $tableAlias,
            ]);
        }

        // Return the table instance
        return $locator->get($tableFullAlias);
    }

    public function onUpdateFieldGuardianRelationId(Event $event, array $attr, $action, ServerRequest $request)
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
        $options['validate'] = true;
        $patch = $this->patchEntity($entity, $data->getArrayCopy(), $options->getArrayCopy());
        $errorCount = count($patch->getErrors());

        if ($errorCount == 0 || ($errorCount == 1 && array_key_exists('guardian_id', $patch->getErrors()))) {
            $this->Session->write('Student.Guardians.new', $data[$this->getAlias()]);
            $event->stopPropagation();

            $action = ['plugin' => $this->controller->getPlugin(), 'controller' => $this->controller->getName(), 'action' => 'GuardianUser', 'add'];
            if ($this->controller->getName() == 'Directories') {
                $action = ['plugin' => $this->controller->getPlugin(), 'controller' => $this->controller->getName(), 'action' => 'StudentGuardianUser', 'add'];
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
            $term = $this->request->getQuery['term'];

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
                    [$UserIdentitiesTable->getAlias() => $UserIdentitiesTable->getTable()],
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
//        die(print_r($entity, true));
        $newButtons = [];
        $queryParams = $this->getQueryString();

        $params = ['id' => $entity->id,
            'user_id' => $entity->student_id,
            'student_id' => $entity->student_id];
        if (isset($queryParams['institution_id'])) {
            $params['institution_id'] = $queryParams['institution_id'];
        }
        if (isset($queryParams['institution_student_id'])) {
            $params['institution_student_id'] = $queryParams['institution_student_id'];
        }
        if (isset($queryParams['user_id'])) {
            $params['user_id'] = $queryParams['user_id'];
        }
        $encodedParams = $this->paramsEncode($params);
        if (isset($buttons['view'])) {
            $viewUrl = $buttons['view']['url'];
            $viewUrl[1] = $encodedParams;
            if (isset($viewUrl['?'])) {
                unset($viewUrl['?']);
            }
            if (isset($viewUrl['queryString'])) {
                unset($viewUrl['queryString']);
            }
            $newButtons['view'] = $buttons['view'];
            $newButtons['view']['url'] = $viewUrl;
//            die(print_r( $newButtons['view'], true));
        }
        if (isset($buttons['edit'])) {
            $editUrl = $buttons['view']['url'];
            $editUrl['1'] = $encodedParams;
            $editUrl['0'] = 'edit';
            if (isset($editUrl['?'])) {
                unset($editUrl['?']);
            }
            if (isset($editUrl['2'])) {
                unset($editUrl['2']);
            }
            if (isset($editUrl['3'])) {
                unset($editUrl['3']);
            }
            if (isset($editUrl['queryString'])) {
                unset($editUrl['queryString']);
            }
            $newButtons['editRelation'] = $buttons['edit'];
            $newButtons['editRelation']['label'] = '<i class="fa fa-pencil"></i>' . __('Edit Relation');
            $newButtons['editRelation']['url'] = $editUrl;
//            die(print_r( $newButtons['view'], true));
        }
        if (isset($buttons['edit'])) {
            $params = ['id' => $entity->_matchingData['Users']->id];
            $encodedParams = $this->paramsEncode($params);
            $editUrl = $buttons['view']['url'];
            $editUrl['plugin'] = 'Directory';
            $editUrl['controller'] = 'Directories';
            $editUrl['action'] = 'Directories';
            $editUrl['1'] = $encodedParams;
            $editUrl['0'] = 'view';
            if (isset($editUrl['?'])) {
                unset($editUrl['?']);
            }
            if (isset($editUrl['2'])) {
                unset($editUrl['2']);
            }
            if (isset($editUrl['3'])) {
                unset($editUrl['3']);
            }
            if (isset($editUrl['queryString'])) {
                unset($editUrl['queryString']);
            }
            $newButtons['viewProfile'] = $buttons['edit'];
            $newButtons['viewProfile']['label'] = '<i class="fa fa-pencil"></i>' . __('Edit Profile');
            $newButtons['viewProfile']['url'] = $editUrl;
//            die(print_r( $newButtons['view'], true));
        }

        if (isset($buttons['remove'])) {
            $newButtons['remove'] = $buttons['remove'];
        }

        $buttons = $newButtons;
        return $buttons;
    }

    public function editButtonAction($action = null)
    {
        if (is_null($action)) {
            return $this->editButtonAction;
        }
        $this->editButtonAction = $action;
    }

    /**
     * Add Autocomplete For staff
     * @author Akshay Patodi <akshay.patodi@mail.valuecoders.com>
     * @ticket POCOR-6592
     */
    public function ajaxUserStaffAutocomplete()
    {
        $this->controller->autoRender = false;
        $this->ControllerAction->autoRender = false;

        if ($this->request->is(['ajax'])) {
            $term = $this->request->getQuery['term'];

            $UserIdentitiesTable = TableRegistry::getTableLocator()->get('User.Identities');

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
                    [$UserIdentitiesTable->getAlias() => $UserIdentitiesTable->getTable()],
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

}
