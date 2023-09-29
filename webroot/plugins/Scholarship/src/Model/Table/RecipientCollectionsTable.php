<?php
namespace Scholarship\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class RecipientCollectionsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('scholarship_recipient_collections');
        parent::initialize($config);

		$this->belongsTo('ScholarshipRecipients', ['className' => 'Scholarship.ScholarshipRecipients', 'foreignKey' => ['recipient_id', 'scholarship_id']]);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Recipients', ['className' => 'User.Users', 'foreignKey' => 'recipient_id']);
        $this->belongsTo('Scholarships', ['className' => 'Scholarship.Scholarships']);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('amount', [
                'comparison' => [
                    'rule' => function ($value, $context) {
                        $recipientId = $context['data']['recipient_id'];
                        $scholarshipId = $context['data']['scholarship_id'];
                        $currentId = $context['data']['id'];
                        $balance = $this->getBalanceAmount($recipientId, $scholarshipId, $currentId);
                        return floatval($value) <= floatval($balance);
                    },
                    'message' => __('Collected Amount must not exceed Balance Amount')
                ],
                'validateDecimal' => [
                    'rule' => ['decimal', null, '/^[0-9]+(\.[0-9]{1,2})?$/'],
                    'message' => __('Value cannot be more than two decimal places')
                ]
            ]);
    }

    public function getBalanceAmount($recipientId, $scholarshipId, $currentId = '')
    {
        $recipientEntity = $this->ScholarshipRecipients->get(['recipient_id' => $recipientId, 'scholarship_id' => $scholarshipId]);
        $approvedAmount = $recipientEntity->approved_amount;

        $where = [
            $this->aliasField('recipient_id') => $recipientId,
            $this->aliasField('scholarship_id') => $scholarshipId
        ];
        if (!empty($currentId)) {
            $where[$this->aliasField('id <> ')] = $currentId;
        }

        $amountUsed = $this->find()
            ->select(['total' => $this->find()->func()->sum('amount')])
            ->where($where)
            ->first();

        return $approvedAmount - $amountUsed->total;
    }
}
