<?php
namespace Scholarship\Model\Table;

use App\Model\Table\AppTable;

class RecipientPaymentStructuresTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('scholarship_recipient_payment_structures');
        parent::initialize($config);

        $this->belongsTo('ScholarshipRecipients', ['className' => 'Scholarship.ScholarshipRecipients', 'foreignKey' => ['recipient_id', 'scholarship_id']]);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Recipients', ['className' => 'User.Users', 'foreignKey' => 'recipient_id']);
        $this->belongsTo('Scholarships', ['className' => 'Scholarship.Scholarships']);
        $this->hasMany('RecipientPaymentStructureEstimates', ['className' => 'Scholarship.RecipientPaymentStructureEstimates', 'foreignKey' => 'scholarship_recipient_payment_structure_id', 'dependent' => true, 'cascadeCallbacks' => true]);
    }
}
