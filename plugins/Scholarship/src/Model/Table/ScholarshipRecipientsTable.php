<?php
namespace Scholarship\Model\Table;

use ArrayObject;

use Cake\Event\Event;
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
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
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
            ->contain([
                'Recipients',
                'Scholarships.FinancialAssistanceTypes',
                'RecipientActivityStatuses'
            ]);
    }

    public function findView(Query $query, array $options)
    {
        return $query
            ->contain([
                'Recipients',
                'Scholarships.FinancialAssistanceTypes',
                'RecipientActivityStatuses',
                'RecipientActivities'
            ]);
    }

    public function findEdit(Query $query, array $options)
    {
        return $query
            ->contain([
                'Recipients',
                'Scholarships.FinancialAssistanceTypes',
                'RecipientActivityStatuses'
            ]);
    }
}
