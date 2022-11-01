<?php
namespace Scholarship\Model\Table;

use App\Model\Table\AppTable;

class RecipientActivitiesTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('scholarship_recipient_activities');
        parent::initialize($config);

		$this->belongsTo('ScholarshipRecipients', ['className' => 'Scholarship.ScholarshipRecipients', 'foreignKey' => ['recipient_id', 'scholarship_id']]);
        $this->belongsTo('Recipients', ['className' => 'User.Users', 'foreignKey' => 'recipient_id']);
        $this->belongsTo('Scholarships', ['className' => 'Scholarship.Scholarships']);
    }
}
