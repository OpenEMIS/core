<?php
namespace Scholarship\Model\Table;

use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class RecipientDisbursementsTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('scholarship_recipient_disbursements');
        parent::initialize($config);

		$this->belongsTo('ScholarshipRecipients', ['className' => 'Scholarship.ScholarshipRecipients', 'foreignKey' => ['recipient_id', 'scholarship_id']]);
        $this->belongsTo('Semesters', ['className' => 'Scholarship.Semesters', 'foreignKey' => 'scholarship_semester_id']);
		$this->belongsTo('DisbursementCategories', ['className' => 'Scholarship.DisbursementCategories', 'foreignKey' => 'scholarship_disbursement_category_id']);
        $this->belongsTo('RecipientPaymentStructures', ['className' => 'Scholarship.RecipientPaymentStructures', 'foreignKey' => 'scholarship_recipient_payment_structure_id']);
		$this->belongsTo('Recipients', ['className' => 'User.Users', 'foreignKey' => 'recipient_id']);
        $this->belongsTo('Scholarships', ['className' => 'Scholarship.Scholarships']);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('amount', 'validateDecimal', [
                'rule' => ['decimal', null, '/^[0-9]+(\.[0-9]{1,2})?$/'],
                'message' => __('Amount cannot be more than two decimal places')
            ])
            ->requirePresence('scholarship_semester_id');
    }
}
