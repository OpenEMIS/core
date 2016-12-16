<?php
namespace Indexes\Model\Table;

use ArrayObject;

use Cake\I18n\Date;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Log\Log;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\HtmlTrait;


class IndexesTable extends ControllerActionTable
{
    use HtmlTrait;

    private $criteriaTypes = [
        // 'RESULT' => [
        //     'name' => 'Results',
        //     'operator' => [1 => '<', 2 => '>'],
        //     'threshold' => ['type' => 'number']
        // ],
        'Institution.StudentBehaviours' => [
            'name' => 'Behavior',
            'operator' => [3 => '='],
            'threshold' => ['type' => 'select', 'lookupModel' => 'Student.Classifications']
        ],
        'Institution.InstitutionStudentAbsences' => [
            'name' => 'Absences',
            'operator' => [1 => '<', 2 => '>'],
            'threshold' => ['type' => 'number']
        ],
        // 'SPECIAL NEED' => [
        //     'name' => 'Special Needs',
        //     'operator' => [1 => '<', 2 => '>'],
        //     'threshold' => ['type' => 'number']
        // ],
        // 'STATUS' => [
        //     'name' => 'Status',
        //     'operator' => [3 => '='],
        //     'threshold' => ['type' => 'select', 'lookupModel' => [2,3,4]]
        // ],
        // // // 'Pre Primary',
        // 'OVERAGE' => [
        //     'name' => 'Overage',
        //     'operator' => [1 => '<', 2 => '>'],
        //     'threshold' => ['type' => 'number']
        // ],
        'Institution.StudentUser' => [
            'name' => 'Genders',
            'operator' => [3 => '='],
            'threshold' => ['type' => 'select', 'lookupModel' => 'User.Genders']
        ],
        // 'GUARDIAN' => [
        //     'name' => 'Guardians',
        //     'operator' => [1 => '<', 2 => '>'],
        //     'threshold' => ['type' => 'number']
        // ],
        // // 'Language'
    ];


    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->hasMany('IndexesCriterias', ['className' => 'Indexes.IndexesCriterias', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->toggle('search', false);
        $this->toggle('remove', false);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.generate'] = 'generate';
        return $events;
    }

    public function generate(Event $event, ArrayObject $extra)
    {
        $requestQuery = $this->request->query;
        $session = $this->request->session();
        $sessionUserId = $session->read('Auth.User.id');
        $sessionIndexId = $session->read('Indexes.Indexes.primaryKey.id');

        $this->fields = []; // reset all the fields

        $extra['config']['form'] = true;
        $extra['elements']['edit'] = ['name' => 'OpenEmis.ControllerAction/edit'];

        $params = [];

        if (array_key_exists('queryString', $requestQuery)) {
            $params = $this->getQueryString();
        }

        $institutionId = array_key_exists('institution_id', $params) ? $params['institution_id'] : 0;
        $userId = array_key_exists('user_id', $params) ? $params['user_id'] : $sessionUserId;
        $indexId = array_key_exists('index_id', $params) ? $params['index_id'] : $sessionIndexId;

        $entity = $this->newEntity();

        if ($this->request->is(['get'])) {
        } else if ($this->request->is(['post', 'put'])) {
            // trigger shell
            $this->triggerUpdateIndexesShell('UpdateIndexes', $institutionId, $userId, $indexId);
        }

        $this->controller->set('data', $entity);
        return $entity;
    }

    public function getCriteriasOptions()
    {
        $criteriaOptions = [];
        foreach ($this->criteriaTypes as $key => $obj) {
            $criteriaOptions [$key] = $obj['name'];
        }

        return $criteriaOptions;
    }

    public function getOperatorParams($criteriaType)
    {
        $operatorParams['label'] = false;
        $operatorParams['type'] = 'select';
        $operatorParams['options'] = $this->criteriaTypes[$criteriaType]['operator'];

        return $operatorParams;
    }

    public function getThresholdParams($criteriaType)
    {
        $thresholdParams['label'] = false;
        $thresholdParams['type'] = $this->criteriaTypes[$criteriaType]['threshold']['type'];

        if ($thresholdParams['type'] == 'select') {
            $model = $this->criteriaTypes[$criteriaType]['threshold']['lookupModel'];
            $thresholdParams['options'] = $this->getOptions($model);
        }

        return $thresholdParams;
    }

    public function getOptions($model)
    {
        $model = TableRegistry::get($model);
        $options = [];
        $options = $model->getThresholdOptions();

        return $options;
    }

    public function onGetCustomCriteriasElement(Event $event, $action, $entity, $attr, $options=[])
    {
        $tableHeaders = $this->getMessage('Indexes.TableHeader');
        $tableCells = [];
        $criteriaOptions = ['' => '-- '.__('Select Criteria').' --'] + $this->getCriteriasOptions();
        $alias = $this->alias();
        $fieldKey = 'indexes_criterias';

        if ($action == 'view') {
            $associated = $entity->extractOriginal([$fieldKey]);
            if (!empty($associated[$fieldKey])) {
                foreach ($associated[$fieldKey] as $i => $obj) {
                    if ($obj['operator'] == 3) {
                        // '=' the threshold is a string
                        $lookupModel = TableRegistry::get($this->criteriaTypes[$obj['criteria']]['threshold']['lookupModel']);
                        $thresholdData = $lookupModel->get($obj['threshold'])->name;
                    } else {
                        // '<' and '>' the threshold is a numeric
                        $thresholdData = $obj['threshold'];
                    }

                    $rowData = [];
                    $rowData[] = $this->criteriaTypes[$obj['criteria']]['name'];
                    $rowData[] = $this->criteriaTypes[$obj['criteria']]['operator'][$obj['operator']];
                    $rowData[] = $thresholdData; // will get form the FO or from the model related
                    $rowData[] = $obj['index_value'];

                    $tableCells[] = $rowData;
                }
            }

        } else if ($action == 'add' || $action == 'edit') {
            $tableHeaders[] = ''; // for delete column
            $Form = $event->subject()->Form;
            $Form->unlockField($alias.".".$fieldKey);

            if ($this->request->is(['get'])) {
                // to read from saved data
                if (!array_key_exists($alias, $this->request->data)) {
                    $this->request->data[$alias] = [$fieldKey => []];
                } else {
                    $this->request->data[$alias][$fieldKey] = [];
                }

                $associated = $entity->extractOriginal([$fieldKey]);
                if (!empty($associated[$fieldKey])) {
                    foreach ($associated[$fieldKey] as $key => $obj) {
                        $this->request->data[$alias][$fieldKey][$key] = [
                            'id' => $obj->id,
                            'criteria' => $obj->criteria,
                            'operator' => $obj->operator,
                            'threshold' => $obj->threshold,
                            'index_value' => $obj->index_value
                        ];
                    }
                }
            }

            // refer to addEditOnAddTrainer for http post
            if ($this->request->data("$alias.$fieldKey")) {
                $associated = $this->request->data("$alias.$fieldKey");

                foreach ($associated as $key => $obj) {
                    $rowData = [];
                    $criteriaType = $obj['criteria'];
                    $operator = $obj['operator'];
                    $threshold = $obj['threshold'];

                    $cell = $criteriaOptions[$criteriaType];
                    if (isset($obj['id'])) {
                        $cell .= $Form->hidden("$alias.$fieldKey.$key.id", ['value' => $obj['id']]);
                    }
                    $cell .= $Form->hidden("$alias.$fieldKey.$key.criteria", ['value' => $criteriaType]);
                    $cell .= $Form->hidden("$alias.$fieldKey.$key.operator", ['value' => $operator]);
                    $cell .= $Form->hidden("$alias.$fieldKey.$key.threshold", ['value' => $threshold]);

                    $rowData[] = $cell;
                    $rowData[] = $Form->input("$alias.$fieldKey.$key.operator", $this->getOperatorParams($criteriaType));
                    $rowData[] = $Form->input("$alias.$fieldKey.$key.threshold", $this->getThresholdParams($criteriaType));
                    $rowData[] = $Form->input("$alias.$fieldKey.$key.index_value", ['type' => 'number', 'label' => false]);
                    $rowData[] = $this->getDeleteButton();
                    $tableCells[] = $rowData;
                }
            }
        }

        $attr['tableHeaders'] = $tableHeaders;
        $attr['tableCells'] = $tableCells;
        $attr['criteriaOptions'] = $criteriaOptions;

        return $event->subject()->renderElement('Indexes.Indexes/' . $fieldKey, ['attr' => $attr]);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('name',['sort' => false]);
        $this->field('modified_user_id',['visible' => true]);
        $this->field('modified',['visible' => true, 'sort' => false]);
        $this->field('generated_on',['sort' => false, 'after' => 'generated_by']);
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain([
            'IndexesCriterias' => [
                'sort' => ['IndexesCriterias.criteria' => 'ASC', 'IndexesCriterias.id' => 'ASC']
            ]
        ]);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($event, $entity);

        // generate buttons
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        $toolbarAttr = [
            'class' => 'btn btn-xs btn-default',
            'data-toggle' => 'tooltip',
            'data-placement' => 'bottom',
            'escape' => false
        ];
        $toolbarButtonsArray['generate']['type'] = 'button';
        $toolbarButtonsArray['generate']['label'] = '<i class="fa fa-refresh"></i>';
        $toolbarButtonsArray['generate']['attr'] = $toolbarAttr;
        $toolbarButtonsArray['generate']['attr']['title'] = __('Generate');
        $toolbarButtonsArray['generate']['url'][0] = 'generate';

        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
        // end generate buttons

    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($event, $entity);
    }

    public function editAfterSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $patchOptions, ArrayObject $extra)
    {
        $fieldKey = 'indexes_criterias';
        $undeletedList = [];
        $originalEntityList = [];
        $originalEntity = [];
        $originalEntity = $entity->extractOriginal([$fieldKey]);

        // get list of original entity
        if (isset($originalEntity[$fieldKey])) {
            foreach ($originalEntity[$fieldKey] as $key => $obj) {
                $originalEntityList[$obj->id] = $obj->criteria;
            }
        }

        // get the list of undeleted records, if all deleted, this list will be emtpy
        if (isset($data[$this->alias()][$fieldKey])) {
            foreach ($data[$this->alias()][$fieldKey] as $key => $obj) {
                if (!empty($obj['id'])) {
                    $undeletedList[$obj['id']] = $obj['criteria'];
                }
            }
        }

        // compare the original list and undeleted list, if not in undeleted list will be deleted.
        if (!empty($originalEntityList)) {
            foreach ($originalEntityList as $key => $obj) {
                if (!array_key_exists($key, $undeletedList)) {
                    $this->IndexesCriterias->delete($this->IndexesCriterias->get($key));
                }
            }
        }
    }

    public function addEditOnAddCriteria(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $alias = $this->alias();
        $fieldKey = 'indexes_criterias';

        if (array_key_exists($alias, $data) && array_key_exists('criteria_type', $data[$alias])) {
            $criteriaType = $data[$alias]['criteria_type'];

            $data[$alias][$fieldKey][] = [
                'criteria' => $criteriaType,
                'operator' => '',
                'threshold' => '',
                'index_value' => ''
            ];

            unset($data[$alias]['criteria_type']);
        }

        //Validation is disabled by default when onReload, however immediate line below will not work and have to disabled validation for associated model like the following lines
        $options['associated'] = [
            'IndexesCriterias' => ['validate' => false]
        ];
    }

    public function setupFields(Event $event, Entity $entity)
    {
        $this->field('generated_by',['visible' => false]);
        $this->field('generated_on',['visible' => false]);
        $this->field('indexes_criterias', ['type' => 'custom_criterias']);

        $this->setFieldOrder(['name', 'indexes_criterias']);
    }

    public function onGetGeneratedBy(Event $event, Entity $entity)
    {
        $userName = '';
        if (isset($entity->generated_by)) {
            $generatedById = $entity->generated_by;

            $Users = TableRegistry::get('Security.Users');
            $userName = $Users->get($generatedById)->first_name . ' ' . $Users->get($generatedById)->last_name;
        }

        return $userName;
    }

    public function onGetFormButtons(Event $event, ArrayObject $buttons)
    {
        $requestQuery = $this->request->query;

        switch ($this->action) {
            case 'generate':
                // on the generate page the save button was renamed to generate button.
                $buttons[0]['name'] = '<i class="fa fa-check"></i> ' . __('Generate');

                // if queryString set means come from institutions index,
                if (array_key_exists('queryString', $requestQuery)) {
                    $cancelUrl = [
                        'plugin' => 'Institution',
                        'controller' => 'Institutions',
                        'action' => 'InstitutionIndexes',
                        'index'
                    ];
                } else {
                    $cancelUrl = $this->url('index');
                }

                $buttons[1]['url'] = $cancelUrl;
                break;
        }
    }

    public function getCriteriasDetails($criteriaKey)
    {
        return $details = $this->criteriaTypes[$criteriaKey];
    }

    public function triggerUpdateIndexesShell($shellName, $institutionId=0, $userId=0, $indexId=0)
    {
        $args = '';
        $args .= !is_null($institutionId) ? ' '.$institutionId : '';
        $args .= !is_null($userId) ? ' '.$userId : '';
        $args .= !is_null($indexId) ? ' '.$indexId : '';

        $cmd = ROOT . DS . 'bin' . DS . 'cake '.$shellName.' '.$args;
        $logs = ROOT . DS . 'logs' . DS . $shellName.'.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;
        $pid = exec($shellCmd);
        Log::write('debug', $shellCmd);
    }
}
