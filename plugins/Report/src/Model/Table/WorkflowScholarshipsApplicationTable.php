<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use App\Model\Table\AppTable;

class WorkflowScholarshipsApplicationTable extends AppTable
{

    public function initialize(array $config) 
    {
        $this->table("scholarship_applications");
        parent::initialize($config);

        $this->belongsTo('Applicants', ['className' => 'User.Users', 'foreignKey' => 'applicant_id']);
        $this->belongsTo('Scholarships', [
            'className' => 'Scholarship.Scholarships'

        ]);
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users', 'foreignKey' => 'assignee_id']);
        $this->hasMany('ApplicationAttachments', [
            'className' => 'Scholarship.ApplicationAttachments',
            'foreignKey' => ['applicant_id', 'scholarship_id'],
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->hasMany('ApplicationInstitutionChoices', [
            'className' => 'Scholarship.ApplicationInstitutionChoices',
            'foreignKey' => ['applicant_id', 'scholarship_id'],
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Report.WorkflowReport');
        $this->addBehavior('Excel', [
            'pages' => false,
            'autoFields' => false
        ]);
    }

    public function implementedEvents() {
        $events = parent::implementedEvents();
        $events['Model.excel.onExcelBeforeQuery'] = 'onExcelBeforeQuery2';
        $events['Model.excel.onExcelUpdateFields'] = 'onExcelUpdateFields2';
        return $events;
    }

    public function onExcelUpdateFields2(Event $event, ArrayObject $settings, ArrayObject $fields) {
        $newFields = [];

        $newFields[0] = [
            'key' => 'name',
            'field' => 'name',
            'type' => 'string',
            'label' => 'Status'
        ];
        $newFields[1] = [
            'key' => 'assigneeFirstLastName',
            'field' => 'assignee_id',
            'type' => 'string',
            'label' => 'Assignee'
        ];
        $newFields[2] = [
            'key' => 'Applicants.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => 'OpenEMIS ID'
        ];
        $newFields[3] = [
            'key' => 'applicantFirstLastName',
            'field' => 'applicant_id',
            'type' => 'string',
            'label' => 'Applicant'
        ];
        $newFields[4] = [
            'key' => 'financial_assistance_type',
            'field' => 'financial_assistance_type',
            'type' => 'string',
            'label' => 'financial_assistance_type'
        ];
        $newFields[5] = [
            'key' => 'Scholarship.name',
            'field' => 'scholarship_name',
            'type' => 'string',
            'label' => 'Scholarship'
        ];
        $newFields[6] = [
            'key' => 'requested_amount',
            'field' => 'requested_amount',
            'type' => 'integer',
            'label' => 'Requested Amount'
        ];
        $newFields[7] = [
            'key' => 'comments',
            'field' => 'comments',
            'type' => 'integer',
            'label' => 'Comments'
        ];
        $fields->exchangeArray($newFields);
    }

    public function onExcelBeforeQuery2(Event $event, ArrayObject $settings, $query) {
        $requestData = json_decode($settings['process']['params']);
        $category = $requestData->category;

        $result = $query
            ->select([
                'name' => 'Statuses.name',
                'assignee_id' => $this->aliasField('assignee_id'),
                'openemis_no' => 'Applicants.openemis_no',
                'applicant_id' => $this->aliasField('applicant_id'),
                'financial_assistance_type' => 'FinancialAssistanceTypes.code',
                'scholarship_name' => 'Scholarships.name',
                'requested_amount' => 'requested_amount',
                'comments' => 'comments'
            ])
            ->contain([
                'Statuses' => [
                    'fields' => [
                        'Statuses.name'
                    ]
                ]   
            ])
            ->contain([
                'Assignees' => [
                    'fields' => [
                        'Assignees.first_name',
                        'Assignees.middle_name',
                        'Assignees.third_name',
                        'Assignees.last_name',
                        'Assignees.preferred_name'
                    ]
                ]   
            ])
            ->contain([
                'Applicants' => [
                    'fields' => [
                        'Applicants.openemis_no',
                        'Applicants.first_name',
                        'Applicants.middle_name',
                        'Applicants.third_name',
                        'Applicants.last_name',
                        'Applicants.preferred_name'
                    ]
                ]   
            ])
            ->contain([
                'Scholarships.FinancialAssistanceTypes' => [
                    'fields' => [
                        'Scholarships.name',
                        'FinancialAssistanceTypes.code'
                    ]
                ]
            ]);
    }
}
