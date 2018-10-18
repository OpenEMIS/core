<?php
namespace Scholarship\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Validation\Validator;

use App\Model\Table\AppTable;

class ScholarshipRecipientsTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('scholarship_recipients');
        parent::initialize($config);

        $this->belongsTo('Recipients', ['className' => 'User.Users', 'foreignKey' => 'recipient_id']);
        $this->belongsTo('Scholarships', ['className' => 'Scholarship.Scholarships']);
        $this->belongsTo('RecipientActivityStatuses', ['className' => 'Scholarship.RecipientActivityStatuses', 'foreignKey' => 'scholarship_recipient_activity_status_id']);
        $this->hasMany('RecipientAcademicStandings', [
            'className' => 'Scholarship.RecipientAcademicStandings',
            'foreignKey' => ['recipient_id', 'scholarship_id'],
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->hasMany('RecipientActivities', [
            'className' => 'Scholarship.RecipientActivities',
            'foreignKey' => ['recipient_id', 'scholarship_id'],
            'dependent' => true,
            'cascadeCallbacks' => true,
            'saveStrategy' => 'append'
        ]);
        $this->hasMany('RecipientCollections', [
            'className' => 'Scholarship.RecipientCollections',
            'foreignKey' => ['recipient_id', 'scholarship_id'],
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->hasMany('RecipientDisbursements', [
            'className' => 'Scholarship.RecipientDisbursements',
            'foreignKey' => ['recipient_id', 'scholarship_id'],
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->hasMany('RecipientPaymentStructureEstimates', [
            'className' => 'Scholarship.RecipientPaymentStructureEstimates',
            'foreignKey' => ['recipient_id', 'scholarship_id'],
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->hasMany('RecipientPaymentStructures', [
            'className' => 'Scholarship.RecipientPaymentStructures',
            'foreignKey' => ['recipient_id', 'scholarship_id'],
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->addBehavior('User.AdvancedNameSearch');
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('approved_amount', [
                'comparison' => [
                    'rule' => function ($value, $context) {
                        return floatval($value) <= floatval($context['data']['total_award_amount']);
                    }
                ],
                'validateDecimal' => [
                    'rule' => ['decimal', null, '/^[0-9]+(\.[0-9]{1,2})?$/']
                ],
                'ruleCheckApprovedAmount' => [
                    'rule' => ['checkApprovedAmount'],
                    'provider' => 'table'
                ]
            ])
            ->allowEmpty('date', function ($context) {
                if (array_key_exists('next_status', $context['data']) && !empty($context['data']['next_status'])) {
                    return false;
                }

                return true;
            })
            ->allowEmpty('next_status', function ($context) {
                if (array_key_exists('date', $context['data']) && !empty($context['data']['date'])) {
                    return false;
                }

                return true;
            });
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if (!$entity->isNew()) {

            if ($entity->has('next_status') && !empty($entity->next_status) && $entity->has('date') && !empty($entity->date)) {
                $statusId = $entity->next_status;

                $prevStatusName = $this->RecipientActivityStatuses->get($entity->scholarship_recipient_activity_status_id)->name;
                $statusName = $this->RecipientActivityStatuses->get($statusId)->name;

                $recipientActivitiesData = [];
                $recipientActivitiesData[] = [
                    'date' => $entity->date,
                    'comments' => $entity->comments,
                    'prev_recipient_activity_status_name' => $prevStatusName,
                    'recipient_activity_status_name' => $statusName,
                    'recipient_id' => $entity->recipient_id,
                    'scholarship_id' => $entity->scholarship_id
                ];

                $data = [
                    'scholarship_recipient_activity_status_id' => $statusId,
                    'recipient_activities' => $recipientActivitiesData
                ];

                $entity = $this->patchEntity($entity, $data);
            }
        }
    }

    public function findIndex(Query $query, array $options)
    {
        return $query
            ->select([
                $this->aliasField('recipient_id'),
                $this->aliasField('scholarship_id'),
                $this->aliasField('approved_amount'),
                $this->aliasField('scholarship_recipient_activity_status_id')
            ])
            ->contain([
                'Recipients' => [
                    'fields' => [
                        'id',
                        'openemis_no',
                        'first_name',
                        'middle_name',
                        'third_name',
                        'last_name',
                        'preferred_name'
                    ]
                ],
                'Scholarships' => [
                    'fields' => [
                        'id',
                        'code',
                        'name',
                        'scholarship_financial_assistance_type_id'
                    ]
                ],
                'Scholarships.FinancialAssistanceTypes' => [
                    'fields' => [
                        'id',
                        'code',
                        'name'
                    ]
                ],
                'RecipientActivityStatuses' => [
                    'fields' => [
                        'id',
                        'name'
                    ]
                ]
            ]);
    }

    public function findView(Query $query, array $options)
    {
        return $query
            ->select([
                $this->aliasField('recipient_id'),
                $this->aliasField('scholarship_id'),
                $this->aliasField('approved_amount'),
                $this->aliasField('scholarship_recipient_activity_status_id'),
                $this->aliasField('modified'),
                $this->aliasField('created')
            ])
            ->contain([
                'Recipients' => [
                    'fields' => [
                        'id',
                        'openemis_no',
                        'first_name',
                        'middle_name',
                        'third_name',
                        'last_name',
                        'preferred_name'
                    ]
                ],
                'Scholarships' => [
                    'fields' => [
                        'id',
                        'code',
                        'name',
                        'maximum_award_amount',
                        'total_amount',
                        'scholarship_financial_assistance_type_id'
                    ]
                ],
                'Scholarships.FinancialAssistanceTypes' => [
                    'fields' => [
                        'id',
                        'code',
                        'name'
                    ]
                ],
                'RecipientActivityStatuses' => [
                    'fields' => [
                        'id',
                        'name'
                    ]
                ],
                'RecipientActivities' => [
                    'fields' => [
                        'id',
                        'date',
                        'comments',
                        'prev_recipient_activity_status_name',
                        'recipient_activity_status_name',
                        'recipient_id',
                        'scholarship_id',
                        'created_user_id',
                        'created'
                    ]
                ],
                'RecipientActivities.CreatedUser' => [
                    'fields' => [
                        'openemis_no',
                        'first_name',
                        'middle_name',
                        'third_name',
                        'last_name',
                        'preferred_name'
                    ]
                ],
                'ModifiedUser' => [
                    'fields' => [
                        'openemis_no',
                        'first_name',
                        'middle_name',
                        'third_name',
                        'last_name',
                        'preferred_name'
                    ]
                ],
                'CreatedUser' => [
                    'fields' => [
                        'openemis_no',
                        'first_name',
                        'middle_name',
                        'third_name',
                        'last_name',
                        'preferred_name'
                    ]
                ]
            ]);
    }

    public function findEdit(Query $query, array $options)
    {
        return $query
            ->select([
                $this->aliasField('recipient_id'),
                $this->aliasField('scholarship_id'),
                $this->aliasField('approved_amount'),
                $this->aliasField('scholarship_recipient_activity_status_id')
            ])
            ->contain([
                'Recipients' => [
                    'fields' => [
                        'id',
                        'openemis_no',
                        'first_name',
                        'middle_name',
                        'third_name',
                        'last_name',
                        'preferred_name'
                    ]
                ],
                'Scholarships' => [
                    'fields' => [
                        'id',
                        'code',
                        'name',
                        'maximum_award_amount',
                        'total_amount',
                        'scholarship_financial_assistance_type_id'
                    ]
                ],
                'Scholarships.FinancialAssistanceTypes' => [
                    'fields' => [
                        'id',
                        'code',
                        'name'
                    ]
                ],
                'RecipientActivityStatuses' => [
                    'fields' => [
                        'id',
                        'name'
                    ]
                ]
            ]);
    }

    public function findSearch(Query $query, array $options)
    {
        $searchOptions = $options['search'];
        $searchOptions['defaultSearch'] = false; // turn off defaultSearch function in page

        $search = $searchOptions['searchText'];
        if (!empty($search)) {
            
        $orConditions = [
             'Scholarships.name LIKE' => $search.'%',
             'FinancialAssistanceTypes.name LIKE' => $search.'%'
        ];         
        // function from AdvancedNameSearchBehavior 
        $query = $this->addSearchConditions($query, ['alias' => 'Recipients', 'searchTerm' => $search, 'OR' => $orConditions]);

        }
        return $query;
    }
}
