<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use App\Model\Table\AppTable;

use Institution\Model\Table\InstitutionStaffTransfersTable as TransferType;

class WorkflowStaffTransferInTable extends AppTable  
{

    public function initialize(array $config) 
    {
        $this->table("institution_staff_transfers");
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('NewInstitutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'new_institution_id']);
        $this->belongsTo('PreviousInstitutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'previous_institution_id']);
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users', 'foreignKey' => 'assignee_id']);
        $this->belongsTo('NewPositions', ['className' => 'Institution.InstitutionPositions', 'foreignKey' => 'new_institution_position_id']);
        $this->belongsTo('NewStaffTypes', ['className' => 'Staff.StaffTypes', 'foreignKey' => 'new_staff_type_id']);
        $this->belongsTo('PreviousInstitutionStaff', ['className' => 'Institution.Staff', 'foreignKey' => 'previous_institution_staff_id']);
        $this->belongsTo('PreviousStaffTypes', ['className' => 'Staff.StaffTypes', 'foreignKey' => 'previous_staff_type_id']);

        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Report.WorkflowReport');
        $this->addBehavior('Excel', [
            'excludes' => ['all_visible'],
            'pages' => false,
            'autoFields' => false
        ]);
    }

    public function onExcelGetTransferType(Event $event, Entity $entity)
    {
        $transferTypeOptions = [
            TransferType::FULL_TRANSFER => __('Full Transfer'),
            TransferType::PARTIAL_TRANSFER => __('Partial Transfer'),
            TransferType::NO_CHANGE => __('No Change')
        ];

        if (array_key_exists($entity->transfer_type, $transferTypeOptions)) {
            return $transferTypeOptions[$entity->transfer_type];
        }

        return '';
    }
}
