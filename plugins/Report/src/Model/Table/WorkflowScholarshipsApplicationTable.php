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
        $this->belongsTo('Scholarships', ['className' => 'Scholarship.Scholarships']);
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
        $events['Model.excel.onExcelBeforeQuery'] = 'onExcelBeforeQuery';
        $events['Model.excel.onExcelUpdateFields'] = 'onExcelUpdateFields';
        return $events;
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields) {
        $newFields = [];

        $newFields[] = [
            'key' => 'name',
            'field' => 'name',
            'type' => 'string',
            'label' => __('Status')
        ];
        $newFields[] = [
            'key' => 'assigneeFirstLastName',
            'field' => 'assignee_id',
            'type' => 'string',
            'label' => __('Assignee')
        ];
        $newFields[] = [
            'key' => 'Applicants.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('OpenEMIS ID')
        ];
        $newFields[] = [
            'key' => 'applicantFirstLastName',
            'field' => 'applicant_id',
            'type' => 'string',
            'label' => __('Applicant')
        ];
        $newFields[] = [
            'key' => 'financial_assistance_type',
            'field' => 'financial_assistance_type',
            'type' => 'string',
            'label' => __('financial_assistance_type')
        ];
        $newFields[] = [
            'key' => 'Scholarship.name',
            'field' => 'scholarship_name',
            'type' => 'string',
            'label' => __('Scholarship')
        ];
        $newFields[] = [
            'key' => 'requested_amount',
            'field' => 'requested_amount',
            'type' => 'integer',
            'label' => __('Requested Amount')
        ];
        $newFields[] = [
            'key' => 'comments',
            'field' => 'comments',
            'type' => 'integer',
            'label' => __('Comments')
        ];
        $fields->exchangeArray($newFields);
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, $query) {
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
                'comments' => $this->aliasField('comments')
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
