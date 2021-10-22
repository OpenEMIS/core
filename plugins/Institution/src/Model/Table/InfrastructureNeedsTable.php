<?php
namespace Institution\Model\Table;
use ArrayObject;

use Cake\I18n\Date;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Validation\Validator;
use Cake\Event\Event;

use App\Model\Table\AppTable;
use App\Model\Table\ControllerActionTable;

class InfrastructureNeedsTable extends ControllerActionTable
{
    private $needPriorities = [
        1 => 'High',
        2 => 'Medium',
        3 => 'Low'
    ];

    public function initialize(array $config)
    {
        $this->table('infrastructure_needs');
        parent::initialize($config);

        $this->belongsTo('InfrastructureNeedTypes', ['className' => 'Institution.InfrastructureNeedTypes', 'foreign_key' => 'infrastructure_need_type_id']);

        $this->belongsToMany('InfrastructureProjects', [
            'className' => 'Institution.InfrastructureProjects',
            'joinTable' => 'infrastructure_projects_needs',
            'foreignKey' => 'infrastructure_need_id',
            'targetForeignKey' => 'infrastructure_project_id',
            'through' => 'Institution.InfrastructureProjectsNeeds',
            'dependent' => true
        ]);

        /* $this->addBehavior('Page.FileUpload', [
            'fieldMap' => ['file_name' => 'file_content'],
            'size' => '2MB'
        ]); */

        $this->addBehavior('Excel',[
            // 'excludes' => ['academic_period_id', 'institution_id'],
            'pages' => ['index'],
        ]);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Restful.Model.isAuthorized'] = ['callable' => 'isAuthorized', 'priority' => 1];
        return $events;
    }

    public function isAuthorized(Event $event, $scope, $action, $extra)
    {
        if ($action == 'download' || $action == 'image') {
            // check for the user permission to download here
            $event->stopPropagation();
            return true;
        }
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->allowEmpty('file_content')
            ->add('code', 'ruleUnique', [
                'rule' => 'validateUnique',
                'provider' => 'table'
            ])
            ->add('name', 'ruleUnique', [
                'rule' => 'validateUnique',
                'provider' => 'table'
            ])
            ->allowEmpty('date_started', function ($context) {
                if (!empty($context['data']['date_completed'])) {
                    return false;
                } else {
                    return true;
                }
            }, __('When date completed is filled, this field cannot be left empty'))
            ->add('date_completed', 'compareWithDateStarted', [
                'rule' => function ($value, $context) {
                    $dateCompleted = new Date ($value);

                    if (!empty($context['data']['date_started'])) {
                        $dateStarted = new Date ($context['data']['date_started']);

                        if ($dateCompleted < $dateStarted) {
                            return false;
                        } else {
                            return true;
                        }
                    }
                    return true;
                }
            ])
        ;
    }

    /* POCOR-6150 */
    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $modelAlias = 'InfrastructureNeeds';
        $userType = '';
        $this->controller->changeUtilitiesHeader($this, $modelAlias, $userType);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('code');
        $this->field('name');
        $this->field('infrastructure_need_type_id');
        $this->field('priority');
        $this->field('description',['visible' => false]);
        $this->field('date_determined',['visible' => false]);
        $this->field('date_started',['visible' => false]);
        $this->field('date_completed',['visible' => false]);
        $this->field('file_name',['visible' => false]);
        $this->field('file_content',['visible' => false]);
        $this->field('comment',['visible' => false]);

        $this->setFieldOrder(['code', 'name', 'infrastructure_need_type_id', 'priority']);

        // set need type filter
        $needTypes = $this->InfrastructureNeedTypes
        ->find('optionList', ['defaultOption' => false])
        ->toArray();

        $needTypeOptions = [null => __('All Need Types')] + $needTypes;
        $extra['needTypes'] = $this->request->query('need_types'); 
        // set need type filter

        // set need priority filter
        $needPriorities = $this->needPriorities;

        $needPrioritiesOptions = [null => __('All Priorities')] + $needPriorities;
        $extra['needPriorities'] = $this->request->query('priority'); 
        // set need priority filter

        $extra['elements']['control'] = [
            'name' => 'Institution.Needs/controls',
            'data' => [
                'needTypeOptions'=> $needTypeOptions,
                'selectedNeedTypes'=> $extra['needTypes'],
                'needPrioritiesOptions'=> $needPrioritiesOptions,
                'selectedNeedPriorities'=> $extra['needPriorities']
            ],
            'order' => 3
        ];

        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        switch ($field) {
            case 'code':
                return __('Code');
            case 'name':
                return __('Name');
            case 'infrastructure_need_type_id':
                return __('Need Type');
            case 'priority':
                return __('Priority');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('code', ['attr' => ['label' => __('Code')]]);
        $this->field('name', ['attr' => ['label' => __('Name')]]);

        $this->fields['infrastructure_need_type_id']['type'] = 'select';
        $this->field('infrastructure_need_type_id', ['attr' => ['label' => __('Need Type')]]);

        $this->fields['priority']['type'] = 'select';
        $this->fields['priority']['options'] = $this->needPriorities;   
        $this->field('priority', ['attr' => ['label' => __('Priority')]]);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $session = $this->request->session();
        $institutionId  = $session->read('Institution.Institutions.id');
        $NeedType = ($this->request->query('need_types')) ? $this->request->query('need_types') : 0;
        $NeedPriority = ($this->request->query('priority')) ? $this->request->query('priority') : 0;

        $query
        ->where([
            $this->aliasField('institution_id') => $institutionId
        ]);
        if($NeedType > 0){
            $query->where([
                $this->aliasField('infrastructure_need_type_id') => $NeedType
            ]);
        }
        if($NeedPriority > 0){
            $query->where([
                $this->aliasField('priority') => $NeedPriority
            ]);
        }
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {
                if($row->priority == 1){
                    $row['priority'] = 'High';
                }elseif($row->priority == 2){
                    $row['priority'] = 'Medium';
                }elseif($row->priority == 3){
                    $row['priority'] = 'Lowa';
                }

                return $row;
            });
        });
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $session = $this->request->session();
        $institutionId  = $session->read('Institution.Institutions.id');
        $NeedType = ($this->request->query('need_types')) ? $this->request->query('need_types') : 0;
        $NeedPriority = ($this->request->query('priority')) ? $this->request->query('priority') : 0;
        
        $query
        ->where([
            $this->aliasField('institution_id') => $institutionId
        ]);

        if($NeedType > 0){
            $query->where([
                $this->aliasField('infrastructure_need_type_id') => $NeedType
            ]);
        }
        if($NeedPriority > 0){
            $query->where([
                $this->aliasField('priority') => $NeedPriority
            ]);
        }
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {
                if($row->priority == 1){
                    $row['priority'] = 'High';
                }elseif($row->priority == 2){
                    $row['priority'] = 'Medium';
                }elseif($row->priority == 3){
                    $row['priority'] = 'Lowa';
                }

                return $row;
            });
        });  
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $extraField[] = [
            'key'   => 'InfrastructureNeeds.code',
            'field' => 'code',
            'type'  => 'string',
            'label' => __('Code')
        ];

        $extraField[] = [
            'key'   => 'InfrastructureNeeds.name',
            'field' => 'name',
            'type'  => 'string',
            'label' => __('Name')
        ];

        $extraField[] = [
            'key'   => 'InfrastructureNeeds.infrastructure_need_type_id',
            'field' => 'infrastructure_need_type_id',
            'type'  => 'string',
            'label' => __('Need Type')
        ];

        $extraField[] = [
            'key'   => '',
            'field' => 'priority',
            'type'  => 'string',
            'label' => __('Priority')
        ];

        $fields->exchangeArray($extraField);
    }
    /* POCOR-6150 */

    public function getNeedTypesOptions()
    {
        // should be auto, if auto the reorder and visible not working
        $needTypeOptions = $this->InfrastructureNeedTypes
            ->find('list')
            ->find('visible')
            ->find('order')
            ->toArray();

        return $needTypeOptions;
    }

    public function getNeedPrioritiesOptions()
    {
        array_walk($this->needPriorities, [$this, "translateArray"]);
        return $this->needPriorities;
    }
}
