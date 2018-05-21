<?php
namespace Scholarship\Model\Table;

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
		$this->belongsTo('Recipients', ['className' => 'User.Users', 'foreignKey' => 'recipient_id']);
        $this->belongsTo('Scholarships', ['className' => 'Scholarship.Scholarships']);
    }
}
