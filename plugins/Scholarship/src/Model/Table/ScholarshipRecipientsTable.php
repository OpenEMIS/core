<?php
namespace Scholarship\Model\Table;

use ArrayObject;

use Cake\Event\EventInterface;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Validation\Validator;

use App\Model\Table\AppTable;
use App\Model\Table\ControllerActionTable;

class ScholarshipRecipientsTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('scholarship_recipients');
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

    //POCOR-9505 -- Start
    public function checkApprovedAmount($value, array $context)
    {
        $RecipientPaymentStructureEstimates =
            TableRegistry::getTableLocator()->get('Scholarship.RecipientPaymentStructureEstimates');
        $RecipientDisbursements =
            TableRegistry::getTableLocator()->get('Scholarship.RecipientDisbursements');
        $RecipientCollections =
            TableRegistry::getTableLocator()->get('Scholarship.RecipientCollections');

        $data = $context['data'];

        $approvedAmount = (float)$value;

        $conditions = [
            'recipient_id' => $data['recipient_id'] ?? null,
            'scholarship_id' => $data['scholarship_id'] ?? null,
        ];

        $estimatedAmount = (float)(
            $RecipientPaymentStructureEstimates->find()
                ->where($conditions)
                ->select([
                    'total' => $RecipientPaymentStructureEstimates->find()->func()->sum('estimated_amount')
                ])
                ->first()
                ->total ?? 0
        );

        $disbursedAmount = (float)(
            $RecipientDisbursements->find()
                ->where($conditions)
                ->select([
                    'total' => $RecipientDisbursements->find()->func()->sum('amount')
                ])
                ->first()
                ->total ?? 0
        );

        $collectedAmount = (float)(
            $RecipientCollections->find()
                ->where($conditions)
                ->select([
                    'total' => $RecipientCollections->find()->func()->sum('amount')
                ])
                ->first()
                ->total ?? 0
        );

        if ($approvedAmount < $estimatedAmount) {
            return $this->getMessage('Scholarship.ScholarshipRecipients.approved_amount.ruleCheckApprovedWithEstimated');
        }

        if ($approvedAmount < $disbursedAmount) {
            return $this->getMessage('Scholarship.ScholarshipRecipients.approved_amount.ruleCheckApprovedWithDisbursed');
        }

        if ($approvedAmount < $collectedAmount) {
            return $this->getMessage('Scholarship.ScholarshipRecipients.approved_amount.ruleCheckApprovedWithCollected');
        }

        return true;
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('approved_amount', [
                'comparison' => [
                    'rule' => function ($value, $context) {

                        if (empty($context['data']['scholarship_id'])) {
                            return true;
                        }

                        $Scholarships = TableRegistry::getTableLocator()
                            ->get('Scholarship.Scholarships');

                        $scholarship = $Scholarships->find()
                            ->select(['total_amount'])
                            ->where(['id' => $context['data']['scholarship_id']])
                            ->first();

                        if (!$scholarship) {
                            return true;
                        }

                        return (float)$value <= (float)$scholarship->total_amount;
                    },
                    'message' => $this->getMessage(
                        'Scholarship.ScholarshipRecipients.approved_amount.comparison'
                    )
                ],
                'validateDecimal' => [
                    'rule' => ['decimal', null, '/^[0-9]+(\.[0-9]{1,2})?$/'],
                    'message' => $this->getMessage('Scholarship.ScholarshipRecipients.approved_amount.validateDecimal')
                ],
                'ruleCheckApprovedAmount' => [
                    'rule' => function ($value, $context) {
                        return $this->checkApprovedAmount($value, $context);
                    }
                ]
            ])
            ->allowEmpty('date', function ($context) {
                if (!empty($context['data']['next_status'])) {
                    return false;
                }
                return true;
            })
            ->allowEmpty('next_status', function ($context) {
                if (!empty($context['data']['date'])) {
                    return false;
                }
                return true;
            });
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        if (isset($extra['toolbarButtons'])) {
            $extra['toolbarButtons']->offsetUnset('add');
        }
    }
    //POCOR-9505 -- End

    public function validationDefaultOrg(Validator $validator): Validator
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

    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
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
    public function indexBeforeAction(EventInterface $event, ArrayObject $extra) {
        $this->field('scholarship_recipient_activity_status_id', ['visible' => false]);
        $this->field('approved_amount', ['visible' => false]);
        $this->field('status', ['attr' => ['label' => __('status')], 'visible' => true, 'sort' => true]);
        $this->field('openemis_no', ['attr' => ['label' => __('status')], 'visible' => true, 'sort' => true]);
        //POCOR-9505 -- Start
        $this->field('recipient', [
            'attr' => ['label' => __('Recipient')],
            'visible' => true,
            'sort' => false
        ]);
        $this->field('scholarship', [
            'attr' => ['label' => __('Scholarship')],
            'visible' => true,
            'sort' => false
        ]);
        //POCOR-9505 -- End
        $this->field('financial_assistance_type', ['attr' => ['label' => __('status')], 'visible' => true, 'sort' => true]);
        $this->field('scholarship_id', ['attr' => ['label' => __('Scholarship Name')], 'visible' => true, 'sort' => true]);
    }

    public function onGetStatus(EventInterface $event, Entity $entity)
    {
        if ($entity->has('recipient_activity_status') && $entity->recipient_activity_status->has('name')) {
            return '<span class="status highlight">' . $entity->recipient_activity_status->name . '</span>';
        }

    }

    public function onGetOpenemisNo(EventInterface $event, Entity $entity)
    {
        if ($entity->has('recipient') && $entity->recipient->has('openemis_no')) {
            return  $entity->recipient->openemis_no;
        }
    }

    //POCOR-9505 -- Start
    public function onGetRecipient(EventInterface $event, Entity $entity)
    {
        $recipient = $entity->getOriginal('recipient');

        if ($recipient instanceof \Cake\ORM\Entity) {
            return $recipient->name ?? null;
        }

        return null;
    }

    public function onGetScholarship(EventInterface $event, Entity $entity)
    {
        $scholarship = $entity->getOriginal('scholarship');

        if ($scholarship instanceof \Cake\ORM\Entity) {
            return $scholarship->name ?? null;
        }

        return null;
    }

    public function onGetFinancialAssistanceType(EventInterface $event, Entity $entity)
    {
        $scholarship = $entity->getOriginal('scholarship');

        if (
            !$scholarship instanceof \Cake\ORM\Entity ||
            empty($scholarship->scholarship_financial_assistance_type_id)
        ) {
            return null;
        }

        $typesTable = TableRegistry::getTableLocator()
            ->get('Scholarship.FinancialAssistanceTypes');

        $type = $typesTable->find()
            ->select(['name'])
            ->where(['id' => $scholarship->scholarship_financial_assistance_type_id])
            ->first();

        return $type->name ?? null;
    }
    //POCOR-9505 -- End

    public function onGetFinancialAssistanceTypeOrg(EventInterface $event, Entity $entity)
    {

        if ($entity->has('scholarship') && $entity->scholarship->has('scholarship_financial_assistance_type_id')) {
            $financialAssistanceTypes = TableRegistry::getTableLocator()->get('Scholarship.FinancialAssistanceTypes');
             $financialAssistanceTypes = $financialAssistanceTypes->find('all', ['conditions' => ['id' => $entity->scholarship->scholarship_financial_assistance_type_id]])->first();
            if (!empty($financialAssistanceTypes)) {
                return  $financialAssistanceTypes->name;
            }
        }

    }

    // public function onGetScholarshipId(EventInterface $event, Entity $entity)
    // {
    //     if ($entity->has('scholarship') && $entity->scholarship->has('name')) {
    //         return  $entity->scholarship->name;
    //     }
    // }

    // public function findIndex(Query $query, array $options)
    // {
    //     return $query
    //         ->select([
    //             $this->aliasField('recipient_id'),
    //             $this->aliasField('scholarship_id'),
    //             $this->aliasField('approved_amount'),
    //             $this->aliasField('scholarship_recipient_activity_status_id')
    //         ])
    //         ->contain([
    //             'Recipients' => [
    //                 'fields' => [
    //                     'id',
    //                     'openemis_no',
    //                     'first_name',
    //                     'middle_name',
    //                     'third_name',
    //                     'last_name',
    //                     'preferred_name'
    //                 ]
    //             ],
    //             'Scholarships' => [
    //                 'fields' => [
    //                     'id',
    //                     'code',
    //                     'name',
    //                     'scholarship_financial_assistance_type_id'
    //                 ]
    //             ],
    //             'Scholarships.FinancialAssistanceTypes' => [
    //                 'fields' => [
    //                     'id',
    //                     'code',
    //                     'name'
    //                 ]
    //             ],
    //             'RecipientActivityStatuses' => [
    //                 'fields' => [
    //                     'id',
    //                     'name'
    //                 ]
    //             ]
    //         ]);
    // }

    // public function findView(Query $query, array $options)
    // {
    //     return $query
    //         ->select([
    //             $this->aliasField('recipient_id'),
    //             $this->aliasField('scholarship_id'),
    //             $this->aliasField('approved_amount'),
    //             $this->aliasField('scholarship_recipient_activity_status_id'),
    //             $this->aliasField('modified'),
    //             $this->aliasField('created')
    //         ])
    //         ->contain([
    //             'Recipients' => [
    //                 'fields' => [
    //                     'id',
    //                     'openemis_no',
    //                     'first_name',
    //                     'middle_name',
    //                     'third_name',
    //                     'last_name',
    //                     'preferred_name'
    //                 ]
    //             ],
    //             'Scholarships' => [
    //                 'fields' => [
    //                     'id',
    //                     'code',
    //                     'name',
    //                     'maximum_award_amount',
    //                     'total_amount',
    //                     'scholarship_financial_assistance_type_id'
    //                 ]
    //             ],
    //             'Scholarships.FinancialAssistanceTypes' => [
    //                 'fields' => [
    //                     'id',
    //                     'code',
    //                     'name'
    //                 ]
    //             ],
    //             'RecipientActivityStatuses' => [
    //                 'fields' => [
    //                     'id',
    //                     'name'
    //                 ]
    //             ],
    //             'RecipientActivities' => [
    //                 'fields' => [
    //                     'id',
    //                     'date',
    //                     'comments',
    //                     'prev_recipient_activity_status_name',
    //                     'recipient_activity_status_name',
    //                     'recipient_id',
    //                     'scholarship_id',
    //                     'created_user_id',
    //                     'created'
    //                 ]
    //             ],
    //             'RecipientActivities.CreatedUser' => [
    //                 'fields' => [
    //                     'openemis_no',
    //                     'first_name',
    //                     'middle_name',
    //                     'third_name',
    //                     'last_name',
    //                     'preferred_name'
    //                 ]
    //             ],
    //             'ModifiedUser' => [
    //                 'fields' => [
    //                     'openemis_no',
    //                     'first_name',
    //                     'middle_name',
    //                     'third_name',
    //                     'last_name',
    //                     'preferred_name'
    //                 ]
    //             ],
    //             'CreatedUser' => [
    //                 'fields' => [
    //                     'openemis_no',
    //                     'first_name',
    //                     'middle_name',
    //                     'third_name',
    //                     'last_name',
    //                     'preferred_name'
    //                 ]
    //             ]
    //         ]);
    // }

    // public function findEdit(Query $query, array $options)
    // {
    //     return $query
    //         ->select([
    //             $this->aliasField('recipient_id'),
    //             $this->aliasField('scholarship_id'),
    //             $this->aliasField('approved_amount'),
    //             $this->aliasField('scholarship_recipient_activity_status_id')
    //         ])
    //         ->contain([
    //             'Recipients' => [
    //                 'fields' => [
    //                     'id',
    //                     'openemis_no',
    //                     'first_name',
    //                     'middle_name',
    //                     'third_name',
    //                     'last_name',
    //                     'preferred_name'
    //                 ]
    //             ],
    //             'Scholarships' => [
    //                 'fields' => [
    //                     'id',
    //                     'code',
    //                     'name',
    //                     'maximum_award_amount',
    //                     'total_amount',
    //                     'scholarship_financial_assistance_type_id'
    //                 ]
    //             ],
    //             'Scholarships.FinancialAssistanceTypes' => [
    //                 'fields' => [
    //                     'id',
    //                     'code',
    //                     'name'
    //                 ]
    //             ],
    //             'RecipientActivityStatuses' => [
    //                 'fields' => [
    //                     'id',
    //                     'name'
    //                 ]
    //             ]
    //         ]);
    // }

    // public function findSearch(Query $query, array $options)
    // {
    //     $searchOptions = $options['search'];
    //     $searchOptions['defaultSearch'] = false; // turn off defaultSearch function in page

    //     $search = $searchOptions['searchText'];
    //     if (!empty($search)) {
            
    //     $orConditions = [
    //          'Scholarships.name LIKE' => $search.'%',
    //          'FinancialAssistanceTypes.name LIKE' => $search.'%'
    //     ];         
    //     // function from AdvancedNameSearchBehavior 
    //     $query = $this->addSearchConditions($query, ['alias' => 'Recipients', 'searchTerm' => $search, 'OR' => $orConditions]);

    //     }
    //     return $query;
    // }
}
