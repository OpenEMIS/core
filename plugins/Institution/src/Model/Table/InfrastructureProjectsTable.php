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
use Cake\Routing\Router;

use App\Model\Table\AppTable;
use App\Model\Table\ControllerActionTable;

use Page\Traits\EncodingTrait;

class InfrastructureProjectsTable extends ControllerActionTable
{
    use EncodingTrait;
    private $projectStatuses = [
        1 => 'Active',
        2 => 'Inactive'
    ];

    public function initialize(array $config)
    {
        $this->table('infrastructure_projects');
        parent::initialize($config);

        $this->belongsTo('InfrastructureProjectFundingSources',   ['className' => 'Institution.InfrastructureProjectFundingSources', 'foreign_key' => 'infrastructure_project_funding_source_id']);

        $this->belongsToMany('InfrastructureNeeds', [
            'className' => 'Institution.InfrastructureNeeds',
            'joinTable' => 'infrastructure_projects_needs',
            'foreignKey' => 'infrastructure_project_id',
            'targetForeignKey' => 'infrastructure_need_id',
            'through' => 'Institution.InfrastructureProjectsNeeds',
            'dependent' => true
        ]);

        // POCOR-6151
        $this->addBehavior('ControllerAction.FileUpload', [
            // 'name' => 'file_name',
            // 'content' => 'file_content',
            'size' => '2MB',
            'contentEditable' => true,
            'allowable_file_types' => 'all',
            'useDefaultName' => true
        ]);
        // POCOR-6151
        // setting this up to be overridden in viewAfterAction(), this code is required
        $this->behaviors()->get('ControllerAction')->config(
            'actions.download.show',
            true
        );

        $this->addBehavior('Excel',[
            'excludes' => ['description', 'funding_source_description', 'contract_amount', 'date_started', 'date_completed', 'file_name', 'file_content', 'comment', 'institution_id'],
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

    /* POCOR-6151 */
    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $modelAlias = 'InfrastructureProjects';
        $userType = '';
        $this->controller->changeUtilitiesHeader($this, $modelAlias, $userType);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('code');
        $this->field('name');
        $this->field('infrastructure_project_funding_source_id');
        $this->field('contract_date');
        $this->field('status');
        $this->field('funding_source_description',['visible' => false]);
        $this->field('contract_amount',['visible' => false]);
        $this->field('description',['visible' => false]);
        $this->field('date_started',['visible' => false]);
        $this->field('date_completed',['visible' => false]);
        $this->field('file_name',['visible' => false]);
        $this->field('file_content',['visible' => false]);
        $this->field('comment',['visible' => false]);

        $this->setFieldOrder(['code', 'name', 'infrastructure_project_funding_source_id', 'contract_date','status']);

        // set funding source filter
        $this->fundingSourceOptions = $this->getFundingSourceOptions();

        $fundingSourceOptions = [null => __('All Funding Source')] + $this->fundingSourceOptions;
        $extra['fundingSource'] = $this->request->query('funding_source'); 
        // set funding source filter

        // set need priority filter
        $projectStatuses = $this->projectStatuses;

        $projectStatusesOptions = [null => __('All Statuses')] + $projectStatuses;
        $extra['projectStatuses'] = $this->request->query('status'); 
        // set need priority filter

        $extra['elements']['control'] = [
            'name' => 'Institution.Projects/controls',
            'data' => [
                'fundingSourceOptions'=> $fundingSourceOptions,
                'selectedFundingSource'=> $extra['fundingSource'],
                'projectStatusesOptions'=> $projectStatusesOptions,
                'selectedProjectStatuses'=> $extra['projectStatuses']
            ],
            'order' => 3
        ];

        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $session = $this->request->session();
        $institutionId  = $session->read('Institution.Institutions.id');
        $fundingSource = ($this->request->query('funding_source')) ? $this->request->query('funding_source') : 0;
        $projectStatuses = ($this->request->query('status')) ? $this->request->query('status') : 0;

        $query
        ->where([
            $this->aliasField('institution_id') => $institutionId
        ]);
        if($fundingSource > 0){
            $query->where([
                $this->aliasField('infrastructure_project_funding_source_id') => $fundingSource
            ]);
        }
        if($projectStatuses > 0){
            $query->where([
                $this->aliasField('status') => $projectStatuses
            ]);
        }
        
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {
                if($row->status == 1){
                    $row['status'] = 'Active';
                }else{
                    $row['status'] = 'Inactive';
                }

                return $row;
            });
        });
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        // determine if download button is shown
        $showFunc = function () use ($entity) {
            $filename = $entity->file_content;
            return !empty($filename);
        };
        $this->behaviors()->get('ControllerAction')->config(
            'actions.download.show',
            $showFunc
        );
        // End
        $this->field('file_name', ['visible' => false]);
        $this->setupFields($entity, $extra);
    }

    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        $session = $this->request->session();
        $institutionId = $session->read('Institution.Institutions.id');

        $this->fields['infrastructure_project_funding_source_id']['type'] = 'select';
        $this->field('infrastructure_project_funding_source_id', ['after' => 'description','attr' => ['label' => __('Funding Source')]]);

        $this->fields['status']['type'] = 'select';
        $this->fields['status']['options'] = $this->projectStatuses;   
        $this->field('status', [
            'after' => 'contract_amount',
            'attr' => ['label' => __('Status')]
            ]
        );

        $InfrastructureNeeds = $this->getNeedsOptions($institutionId);
        $this->field('infrastructure_needs', [
            'type' => 'chosenSelect',
            'options' => $InfrastructureNeeds,
            'before' => 'file_content',
            'attr' => [
                'data-placeholder'=> 'Select Needs',
                'label' => __('Associated Needs')
            ]
        ]);

        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['before' => 'comment','attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
    }

    public function setupFields(Entity $entity, ArrayObject $extra)
    { 
        if($extra['elements']['view']){
            if($entity->status == 1){
                $entity['status'] = 'Active';
            }else{
                $entity['status'] = 'Inactive';
            }

            // NEEDS segment for view
            $associatedNeeds = $this->getAssociatedRecords($entity);
            $entity['associated_needs'] = $associatedNeeds;

            if (!empty($associatedNeeds)) {
                $this->field('Associated Needs', [
                    'type' => 'element',
                    'before' => 'file_content',
                    'element' => 'Institution.AssociatedNeeds/details',
                    'visible' => ['view'=>true],
                    'data' => $associatedNeeds
                ]);
            }
            // NEEDS segment for view
        }

        $this->field('infrastructure_project_funding_source_id',['after' => 'description','visible' => ['view' => true,'edit' => true]]);
        $this->field('file_name', ['type' => 'hidden']);
        $this->field('file_content', ['after' => 'date_completed','attr' => ['label' => __('Attachment')], 'visible' => ['view' => true, 'edit' => true]]);
    }
    // POCOR-6151

    public function findEdit(Query $query, array $options)
    {
        return $query->contain(['InfrastructureNeeds']);
    }

    // NEEDS segment for view POCOR-6151
    private function getAssociatedRecords($entity)
    {
        $InfrastructureProjectsNeeds = TableRegistry::get('Institution.InfrastructureProjectsNeeds');
        
        $needData = $InfrastructureProjectsNeeds->find()
        ->contain(['InfrastructureNeeds'])
        ->where([$InfrastructureProjectsNeeds->aliasField('infrastructure_project_id') => $entity->id])
        ->all();

        $associatedRecords = [];
        if (count($needData)) {
            $institutionId = $entity->institution_id;
            $encodedInstitutionId = $this->paramsEncode(['id' => $institutionId]);

            foreach ($needData as $key => $need) {
                $encodedNeedId = $this->encode(['id' => $need->infrastructure_need_id]);
                $needName = $need->infrastructure_need->name;

                $url = Router::url([
                    'plugin' => 'Institution',
                    'controller' => 'InfrastructureNeeds',
                    'action' => 'view',
                    'institutionId' => $encodedInstitutionId,
                    $encodedNeedId
                ]);

                $associatedRecords[] = [
                    'need_name' => $needName,
                    'link' => '<a href=' . $url . ' > ' . $needName . '</a>'
                ];
            }
        }

        return $associatedRecords;
    }
    // NEEDS segment for view

    // for getting multiple selected Dropdown in edit
    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain([
            'InfrastructureNeeds'
        ]);
    }
    // for getting multiple selected Dropdown in edit
    

    public function getFundingSourceOptions()
    {
        // should be auto, if auto the reorder and visible not working
        $fundingSourceOptions = $this->InfrastructureProjectFundingSources
            ->find('list')
            ->find('visible')
            ->find('order')
            ->toArray();

        return $fundingSourceOptions;
    }

    public function getProjectStatusesOptions()
    {
        return $this->projectStatuses;
    }

    public function getNeedsOptions($institutionId)
    {
        $needsOptions = $this->InfrastructureNeeds->find('list')->where([$this->InfrastructureNeeds->aliasField('institution_id') => $institutionId])->toArray();

        return $needsOptions;
    }

    // POCOR-6151 Export Functionality
    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $session = $this->request->session();
        $institutionId  = $session->read('Institution.Institutions.id');
        $fundingSource = ($this->request->query('funding_source')) ? $this->request->query('funding_source') : 0;
        $projectStatuses = ($this->request->query('status')) ? $this->request->query('status') : 0;

        $query
        ->where([
            $this->aliasField('institution_id') => $institutionId
        ]);
        if($fundingSource > 0){
            $query->where([
                $this->aliasField('infrastructure_project_funding_source_id') => $fundingSource
            ]);
        }
        if($projectStatuses > 0){
            $query->where([
                $this->aliasField('status') => $projectStatuses
            ]);
        }
        
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {
                if($row->status == 1){
                    $row['status'] = 'Active';
                }else{
                    $row['status'] = 'Inactive';
                }

                return $row;
            });
        }); 
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $extraField[] = [
            'key'   => 'InfrastructureProjects.code',
            'field' => 'code',
            'type'  => 'string',
            'label' => __('Code')
        ];

        $extraField[] = [
            'key'   => 'InfrastructureProjects.name',
            'field' => 'name',
            'type'  => 'string',
            'label' => __('Name')
        ];

        $extraField[] = [
            'key'   => 'InfrastructureProjects.infrastructure_project_funding_source_id',
            'field' => 'infrastructure_project_funding_source_id',
            'type'  => 'string',
            'label' => __('Funding Source')
        ];

        $extraField[] = [
            'key'   => 'InfrastructureProjects.contract_date',
            'field' => 'contract_date',
            'type'  => 'date',
            'label' => __('Contract Date')
        ];

        $extraField[] = [
            'key'   => 'InfrastructureProjects.status',
            'field' => 'status',
            'type'  => 'string',
            'label' => __('Status')
        ];

        $fields->exchangeArray($extraField);
    }
    // POCOR-6151 Export Functionality
}
