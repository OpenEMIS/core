<?php

namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Network\Session;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use Cake\Datasource\ConnectionManager;

/**
 * Get the Staff  details in excel file 
 * POCOR-6581
 */
class InstitutionStandardMarksEnteredTable extends AppTable
{

    public function initialize(array $config)
    {
         $this->table('assessment_item_results');
        parent::initialize($config);

        $this->belongsTo('Assessments', ['className' => 'Assessment.Assessments']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('AssessmentGradingOptions', ['className' => 'Assessment.AssessmentGradingOptions']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('AssessmentPeriods', ['className' => 'Assessment.AssessmentPeriods']);
        $this->addBehavior('Report.ReportList');
    }

    public function beforeAction(Event $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('feature', ['select' => false]);
        $this->ControllerAction->field('format');
        $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);

        $controllerName = $this->controller->name;
        $institutions_crumb = __('Institutions');
        $parent_crumb       = __('Statistics');
        $reportName         = __('Standard');
        
        //# START: Crumb
        $this->Navigation->removeCrumb($this->getHeader($this->alias));
        $this->Navigation->addCrumb($institutions_crumb . ' ' . $parent_crumb);
        //# END: Crumb
        $this->controller->set('contentHeader', __($institutions_crumb) . ' ' . $parent_crumb . ' - ' . $reportName);
    }

    public function addBeforeAction(Event $event)
    {
        $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);
    }

    public function onUpdateFieldFormat(Event $event, array $attr, $action, Request $request)
    {
        $session = $this->request->session();
        $institution_id = $session->read('Institution.Institutions.id');
        $request->data[$this->alias()]['current_institution_id'] = $institution_id;
        $request->data[$this->alias()]['institution_id'] = $institution_id;
        if ($action == 'add') {
            $attr['value'] = 'xlsx';
            $attr['attr']['value'] = 'Excel';
            $attr['type'] = 'readonly';
            return $attr;
        }
    }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request)
    {
        $options = $options = $this->controller->getInstitutionStatisticStandardReportFeature();
        $attr['options'] = $options;
        $attr['onChangeReload'] = true;
        if (!(isset($this->request->data[$this->alias()]['feature']))) {
            $option = $attr['options'];
            reset($option);
            $this->request->data[$this->alias()]['feature'] = key($option);
        }
        return $attr;
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if (isset($request->data[$this->alias()]['feature'])) {
            $feature                = $this->request->data[$this->alias()]['feature'];
            $AcademicPeriodTable    = TableRegistry::get('AcademicPeriod.AcademicPeriods');
            $academicPeriodOptions  = $AcademicPeriodTable->getYearList();
            $currentPeriod          = $AcademicPeriodTable->getCurrent();
            $attr['options']        = $academicPeriodOptions;
            $attr['type']           = 'select';
            $attr['select']         = false;
            $attr['onChangeReload'] = true;
            if (empty($request->data[$this->alias()]['academic_period_id'])) {
                $request->data[$this->alias()]['academic_period_id'] = $currentPeriod;
            }
            return $attr;
        }
    }

    public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets)
    {
        $sheets[] = [
            'name' => $this->alias(),
            'table' => $this,
            'query' => $this->find(),
            'orientation' => 'landscape'
        ];
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData           = json_decode($settings['process']['params']);
        $academicPeriodId      = $requestData->academic_period_id;
        $institutionId         = $requestData->institution_id;
        $assessmentId         = $requestData->assessment_id;
        $assessmentPeriodId   = $requestData->assessment_period_id;
        $where = [];
        if ($assessmentId != 0) {
               $where[$this->aliasField('assessment_id')] = $assessmentId;
        }
        $where[$this->aliasField('assessment_period_id')] = $assessmentPeriodId;
        $where[$this->aliasField('academic_period_id')] = $academicPeriodId;
            $query
            ->select([
                $this->aliasField('student_id'),
                $this->aliasField('institution_id'),
                $this->aliasField('education_grade_id'),
                $this->aliasField('institution_class_id'),
                $this->aliasField('assessment_id'),
                $this->aliasField('assessment_period_id'),
                $this->aliasField('academic_period_id'),
            ])
            ->contain([
                'Users' => [
                   'fields' => [
                      'openemis_no' => 'Users.openemis_no',
                        'first_name' => 'Users.first_name',
                        'middle_name' => 'Users.middle_name',
                        'third_name' => 'Users.third_name',
                        'last_name' => 'Users.last_name',
                   ]
             ],
             'AcademicPeriods' => [
                    'fields' => [
                        'academic_period_id'=>'AcademicPeriods.id',
                        'academic_period'=>'AcademicPeriods.name'
                    ]
                ],
                'Institutions' => [
                    'fields' => [
                       'institution_name'=> 'Institutions.name',
                        'institution_code'=>'Institutions.code'
                    ]
                ],
                'InstitutionClasses' => [
                    'fields' => [
                       'institution_Class_name'=> 'InstitutionClasses.name'
                    ]
                ],
                'EducationGrades' => [
                    'fields' => [
                       'education_grade_name'=> 'EducationGrades.name',
                    ]
                ],
                'Assessments' => [
                    'fields' => [
                       'assessments_name'=> 'Assessments.name',
                    ]
                ],
                'AssessmentPeriods' => [
                    'fields' => [
                       'assessment_periods_name'=> 'AssessmentPeriods.name',
                    ]
                ],
            ])
            
            /*->leftJoin(
                [$institution->alias() => $institution->table()],
                [$institution->aliasField('id = ') . 'InstitutionStaff.institution_id']
            )*/
            
            //->group(['InstitutionStaff.staff_id'])
        ->Where($where);
            $query->formatResults(function (\Cake\Collection\CollectionInterface $results)
            {
                return $results->map(function ($row)
                {
                    $row['referrer_full_name'] = $row['first_name'] .' '. $row['last_name'];
                    return $row;
                });
            });
        
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $newFields = [];
        $newFields[] = [
            'key'   => 'positionsNumber',
            'field' => 'positionsNumber',
            'type'  => 'string',
            'label' => __('Number'),
        ];
        $newFields[] = [
            'key'   => 'referrer_position_type',
            'field' => 'referrer_position_type',
            'type'  => 'string',
            'label' => __('Title'),
        ];
        $newFields[] = [
            'key'   => 'grade',
            'field' => 'grade',
            'type'  => 'string',
            'label' => __('Grade'),
        ];
        $newFields[] = [
            'key'   => 'institution',
            'field' => 'institution',
            'type'  => 'string',
            'label' => __('Institution'),
        ];
        $newFields[] = [
            'key'   => 'assignee_user_full_name',
            'field' => 'assignee_user_full_name',
            'type'  => 'string',
            'label' => __('Assignee'),
        ];
        $newFields[] = [
            'key'   => 'referrer_is_home',
            'field' => 'referrer_is_home',
            'type'  => 'string',
            'label' => __('Homeroom Teacher'),
        ];
        $newFields[] = [
            'key'   => 'openemis_no',
            'field' => 'openemis_no',
            'type'  => 'integer',
            'label' => __('OpenEMIS ID'),
        ];
        $newFields[] = [
            'key'   => 'referrer_full_name',
            'field' => 'referrer_full_name',
            'type'  => 'string',
            'label' => __('Staff'),
        ];
        $newFields[] = [
            'key'   => 'fte',
            'field' => 'fte',
            'type'  => 'integer',
            'label' => __('FTE'),
        ];
        $newFields[] = [
            'key'   => 'staffStatus',
            'field' => 'staffStatus',
            'type'  => 'string',
            'label' => __('Status'),
        ];
        $newFields[] = [
            'key'   => 'identityType',
            'field' => 'identityType',
            'type'  => 'string',
            'label' => __('Identity Type'),
        ];
        
        $newFields[] = [
            'key'   => 'identity_number',
            'field' => 'identity_number',
            'type'  => 'integer',
            'label' => __('Identity Number'),
        ];
        
        $newFields[] = [
            'key'   => 'academic_period',
            'field' => 'academic_period',
            'type'  => 'integer',
            'label' => __('Academic Period'),
        ];
        $newFields[] = [
            'key'   => 'class_name',
            'field' => 'class_name',
            'type'  => 'string',
            'label' => __('Classes'),
        ];
        $newFields[] = [
            'key'   => 'subject_name',
            'field' => 'subject_name',
            'type'  => 'string',
            'label' => __('Subject'),
        ];
        $newFields[] = [
            'key'   => 'staff_absence_day',
            'field' => 'staff_absence_day',
            'type'  => 'integer',
            'label' => __('Absences'),
        ];

        $fields->exchangeArray($newFields);
    }

    /**
     * Get student identity type
     */
    public function onExcelGetIdentityType(Event $event, Entity $entity)
    {
        $return = [];
        if ($entity->has('user')) {
            if ($entity->user->has('identities')) {
                if (!empty($entity->user->identities)) {
                    $identities = $entity->user->identities;
                    foreach ($identities as $key => $value) {
                        if ($value->identity_type->default == 1) {                            
                            $return[] = $value->identity_type->name;
                        }
                    }
                }
            }
        }

        return implode(', ', array_values($return));
    } 


    
}
