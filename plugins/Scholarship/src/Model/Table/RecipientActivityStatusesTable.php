<?php
namespace Scholarship\Model\Table;

use App\Model\Table\ControllerActionTable;

class RecipientActivityStatusesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
    	$this->table('scholarship_recipient_activity_statuses');
        parent::initialize($config);

        $this->hasMany('RecipientActivities', ['className' => 'Scholarship.RecipientActivities', 'foreignKey' => '	scholarship_recipient_activity_status_id', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->addBehavior('FieldOption.FieldOption');
        $this->setDeleteStrategy('restrict');
    }
}
