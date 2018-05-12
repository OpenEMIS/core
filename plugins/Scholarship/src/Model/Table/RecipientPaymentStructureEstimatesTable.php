<?php
namespace Scholarship\Model\Table;

use App\Model\Table\AppTable;

class RecipientPaymentStructureEstimatesTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('scholarship_recipient_payment_structure_estimates');
        parent::initialize($config);

		$this->belongsTo('ScholarshipRecipients', ['className' => 'Scholarship.ScholarshipRecipients', 'foreignKey' => ['recipient_id', 'scholarship_id']]);
		$this->belongsTo('DisbursementCategories', ['className' => 'Scholarship.DisbursementCategories', 'foreignKey' => 'scholarship_disbursement_category_id']);
		$this->belongsTo('RecipientPaymentStructures', ['className' => 'Scholarship.RecipientPaymentStructures', 'foreignKey' => 'scholarship_recipient_payment_structure_id']);
		$this->belongsTo('Recipients', ['className' => 'User.Users', 'foreignKey' => 'recipient_id']);
        $this->belongsTo('Scholarships', ['className' => 'Scholarship.Scholarships']);
    }
}
