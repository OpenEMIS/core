<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;

class StudentsRiskAssessmentTable extends AppTable  {
	public function initialize(array $config) {
		$this->table('institution_students');
		parent::initialize($config);
        $this->addBehavior('Report.ReportList');
		$this->addBehavior('Excel', [
            'pages' => false
        ]);
       
	}

    public function onExcelBeforeStart (Event $event, ArrayObject $settings, ArrayObject $sheets)
    {
        $sheets[] = [
            'name' => $this->alias(),
            'table' => $this,
            'query' => $this->find(),
            'orientation' => 'landscape'
        ];
    }

	public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) {
        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;
        $institutionId = $requestData->institution_id;
        $riskType = $requestData->risk_type;
        $conditions = [];
        if (!empty($academicPeriodId)) {
        $conditions[$this->aliasField('academic_period_id')] = $academicPeriodId;
        }
        if (!empty($institutionId) && $institutionId !='-1') {
        $conditions['Institutions.id'] = $institutionId;
        }
        if (!empty($riskType)) {
        $conditions['InstitutionRisks.risk_id'] = $riskType;
        }
    
        $query
            ->select([
                'student_name' => $query->func()->concat([
                    'Users.first_name' => 'literal',
                    " ",
                    'Users.last_name' => 'literal'
                    ]),
                'student_identity_number' => 'Users.identity_number',
                'student_openemis_no'=> 'Users.openemis_no',
                'student_identity_type_id'=> 'Users.identity_type_id',
                'institution_name' => 'Institutions.name',
                'institution_code' => 'Institutions.code',
                'academic_period_name' => 'AcademicPeriods.name',
                'risk_generated_on' => 'InstitutionRisks.generated_on',
                'risk_generated_by' => 'Users.first_name',
                'risk_index' => 'Risks.id',
                'risk_type' => 'Risks.name'
             ])
            ->leftJoin(['Users' => 'security_users'], [
                            'Users.id = ' . $this->aliasfield('student_id')
                        ])
            ->leftJoin(['InstitutionStudents' => 'institution_students'], [
                        'Users.id = ' . 'InstitutionStudents.student_id'
                        ])
            ->leftJoin(['AcademicPeriods' => 'academic_periods'], [
                        'InstitutionStudents.academic_period_id = ' . 'AcademicPeriods.id'
                        ])           
            ->leftJoin(['Institutions' => 'institutions'], [
                        'InstitutionStudents.institution_id = ' . 'Institutions.id'
                        ])
            ->leftJoin(['InstitutionRisks' => 'institution_risks'], [
                        'Institutions.id = ' . 'InstitutionRisks.institution_id'
                       ],
                        ['Users.id = ' . 'InstitutionRisks.generated_by'
                        ])
            ->leftJoin(['Risks' => 'risks'], [
                        'InstitutionRisks.risk_id = ' . 'Risks.id'
                        ])
                  
            ->where($conditions);       
                           
    }
    
	public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $cloneFields = $fields->getArrayCopy();


        $extraFields[] = [
            'key' => 'Institutions.name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Name')
        ];

        $extraFields[] = [
            'key' => 'Institutions.code',
            'field' => 'institution_code',
            'type' => 'string',
            'label' => __('Code')
        ];

        $extraFields[] = [
            'key' => 'AcademicPeriods.name',
            'field' => 'academic_period_name',
            'type' => 'string',
            'label' => __('Academic Period')
        ];

        $extraFields[] = [
            'key' => 'Risks.first_name',
            'field' => 'risk_type',
            'type' => 'string',
            'label' => __('Risk Type')
        ];

        $extraFields[] = [
            'key' => 'Users.openemis_no',
            'field' => 'student_openemis_no',
            'type' => 'string',
            'label' => __('OpenEMIS ID')
        ];

        $extraFields[] = [
            'key' => 'Users.identity_type_id',
            'field' => 'student_identity_number',
            'type' => 'string',
            'label' => __('Default Identity Type')
        ];

        $extraFields[] = [
            'key' => 'Users.identity_number',
            'field' => 'student_identity_number',
            'type' => 'string',
            'label' => __('Identity Number')
        ];

        $extraFields[] = [
            'key' => '',
            'field' => 'student_name',
            'type' => 'string',
            'label' => __('Student First Name')
        ]; 

        $extraFields[] = [
            'key' => 'Risks.id',
            'field' => 'risk_index',
            'type' => 'string',
            'label' => __('Risk Index')
        ];

        $extraFields[] = [
            'key' => 'Users.name',
            'field' => 'risk_generated_by',
            'type' => 'string',
            'label' => __('Risk Generated By')
        ];

        $extraFields[] = [
            'key' => 'InstitutionRisks.generated_on',
            'field' => 'risk_generated_on',
            'type' => 'string',
            'label' => __('Risk Generated On')
        ];
      

        $newFields = $extraFields;
        
        $fields->exchangeArray($newFields);
    }

}
