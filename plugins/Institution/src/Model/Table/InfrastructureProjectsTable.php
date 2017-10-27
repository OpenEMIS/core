<?php
namespace Institution\Model\Table;

use Cake\I18n\Date;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\Validation\Validator;
use Cake\Event\Event;

use App\Model\Table\AppTable;

class InfrastructureProjectsTable extends AppTable
{
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

        $this->addBehavior('Page.FileUpload', [
            'fieldMap' => ['file_name' => 'file_content'],
            'size' => '2MB'
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

    public function findEdit(Query $query, array $options)
    {
        return $query->contain(['InfrastructureNeeds']);
    }

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
}
