<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;

class InstitutionSubjectsTable extends AppTable  {
	public function initialize(array $config) {
		$this->table('institution_subjects');
		parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        
	$this->addBehavior('Excel', [
            'autoFields' => false
        ]);
		$this->addBehavior('Report.ReportList');
		$this->addBehavior('Report.InstitutionSecurity');
	}

	public function beforeAction(Event $event) {
		$this->fields = [];
		$this->ControllerAction->field('feature');
		$this->ControllerAction->field('format');
	}

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) 
    {
        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;
        $institutionId = $requestData->institution_id;
        
        $conditions = [];
        if (!empty($academicPeriodId)) {
            $conditions[$this->aliasField('academic_period_id')] = $academicPeriodId;
        }
        if (!empty($institutionId)) {
            $conditions['Institutions.id'] = $institutionId;
        }
        
        if (!empty($requestData->education_subject_id)) {
            $conditions[$this->aliasField('education_subject_id')] = $requestData->education_subject_id;
        }
        
        $InstitutionClassSubjects = TableRegistry::get('Institution.InstitutionClassSubjects');
        $InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
        $InstitutionSubjectStaff = TableRegistry::get('Institution.InstitutionSubjectStaff');
        $Staff = TableRegistry::get('User.Users');

        $query
            ->select([
                'institution_code' => 'Institutions.code',
                'institution_name' => $query->func()->concat(['Institutions.code' => 'literal', ' - ', 'Institutions.name' => 'literal']),
                'area_code' => 'Areas.code',
                // 'area_name' => $query->func()->concat(['Areas.code' => 'literal', ' - ', 'Areas.name' => 'literal']),
                'area_name' => 'Areas.name',
                'area_administrative_code' => 'AreaAdministratives.code',
                'area_administrative_name' => 'AreaAdministratives.name',
                'EducationGrades.name',
                'class_name' => 'InstitutionClasses.name',
                'institution_class_id' => 'InstitutionClasses.id',
                'AcademicPeriods.name',
                'total_students' => $query
                    ->newExpr()
                    ->add($this->aliasField('total_male_students'))
                    ->add($this->aliasField('total_female_students'))
                    ->tieWith('+'),
                $this->aliasField('name'),
                $this->aliasField('no_of_seats'),
                $this->aliasField('total_male_students'),
                $this->aliasField('total_female_students'),
                $this->aliasField('institution_id'),
                $this->aliasField('education_grade_id'),
                $this->aliasField('education_subject_id'),
                $this->aliasField('academic_period_id'),
                $this->aliasField('academic_period_id'),
            ])
            ->contain([
                'Institutions.Areas',
                'Institutions.AreaAdministratives',
                'EducationGrades',
                'EducationSubjects',
                'AcademicPeriods'
            ])
            ->leftJoin([$InstitutionClassSubjects->alias() => $InstitutionClassSubjects->table()], [
                $this->aliasField('id =') . $InstitutionClassSubjects->aliasField('institution_subject_id')
            ])
            ->leftJoin([$InstitutionClasses->alias() => $InstitutionClasses->table()], [
                $InstitutionClassSubjects->aliasField('institution_class_id =') . $InstitutionClasses->aliasField('id')
            ])
            ->where($conditions);
            
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {
                
                $areas1 = TableRegistry::get('areas');
                $areasData = $areas1
                            ->find()
                            ->where([$areas1->alias('code')=>$row->area_code])
                            ->first();
                $row['region_code'] = '';            
                $row['region_name'] = '';
                if(!empty($areasData)){
                    $areas = TableRegistry::get('areas');
                    $areaLevels = TableRegistry::get('area_levels');
                    $institutions = TableRegistry::get('institutions');
                    $val = $areas
                                ->find()
                                ->select([
                                    $areas1->aliasField('code'),
                                    $areas1->aliasField('name'),
                                    ])
                                ->leftJoin(
                                    [$areaLevels->alias() => $areaLevels->table()],
                                    [
                                        $areas->aliasField('area_level_id  = ') . $areaLevels->aliasField('id')
                                    ]
                                )
                                ->leftJoin(
                                    [$institutions->alias() => $institutions->table()],
                                    [
                                        $areas->aliasField('id  = ') . $institutions->aliasField('area_id')
                                    ]
                                )    
                                ->where([
                                    $areaLevels->aliasField('level !=') => 1,
                                    $areas->aliasField('id') => $areasData->parent_id
                                ])->first();
                    
                    if (!empty($val->name) && !empty($val->code)) {
                        $row['region_code'] = $val->code;
                        $row['region_name'] = $val->name;
                    }
                }            
                
                return $row;
            });
        });
               
    }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request) {
            $attr['options'] = $this->controller->getFeatureOptions('Institutions');
            return $attr;
    }
     
    public function onExcelGetStaffName(Event $event, Entity $entity)
    {
        $InstitutionSubjects = TableRegistry::get('Report.InstitutionSubjects');
        $InstitutionClassSubjects = TableRegistry::get('Institution.InstitutionClassSubjects');
        $InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
        $InstitutionSubjectStaff = TableRegistry::get('Institution.InstitutionSubjectStaff');
        $Staff = TableRegistry::get('User.Users');
        $conditions = [
            $this->aliasField('education_subject_id') => $entity->education_subject_id,
            $this->aliasField('institution_id') => $entity->institution_id,
            $this->aliasField('education_grade_id') => $entity->education_grade_id,
            $this->aliasField('academic_period_id') => $entity->academic_period_id,
            $InstitutionClassSubjects->aliasField('institution_class_id =') => $entity->institution_class_id,
            ];

        $staffResult = $InstitutionSubjects
                ->find()
                ->select([                    
                    'staff_id' => 'InstitutionSubjectStaff.staff_id',
                    'Users.openemis_no',
                    'Users.first_name',
                    'Users.last_name'                    
                ])
                ->leftJoin([$InstitutionClassSubjects->alias() => $InstitutionClassSubjects->table()], [
                    $this->aliasField('id =') . $InstitutionClassSubjects->aliasField('institution_subject_id')
                ])
                ->leftJoin([$InstitutionClasses->alias() => $InstitutionClasses->table()], [
                    $InstitutionClassSubjects->aliasField('institution_class_id =') . $InstitutionClasses->aliasField('id')
                ])
                ->leftJoin([$InstitutionSubjectStaff->alias() => $InstitutionSubjectStaff->table()], [
                    $InstitutionSubjectStaff->aliasField('institution_subject_id =') . $InstitutionClassSubjects->aliasField('institution_subject_id')
                ])
                ->leftJoin([$Staff->alias() => $Staff->table()], [
                    $Staff->aliasField('id =') . $InstitutionSubjectStaff->aliasField('staff_id')
                ])
                ->where($conditions)
                ->hydrate(false)
                ->toArray()
                ;  
        $staffName = [];
        foreach($staffResult as $result){
            if(!empty($result['Users']['openemis_no'])){
                $staffName[] = $result['Users']['openemis_no'].' - '.$result['Users']['first_name'].' '.$result['Users']['last_name'];
            }
        }
       
        return implode(',', $staffName);
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields) 
    {   
        foreach ($fields as $key => $value) {
            if ($value['field'] == 'education_subject_id') {
                $fields[$key] = array('key' => 'InstitutionClasses.name',
                    'field' => 'class_name',
                    'type' => 'string',
                    'label' => __('Institution Class'));
            }
        }
        
        $cloneFields = $fields->getArrayCopy();
        $newFields = [];
        
        foreach ($cloneFields as $key => $value) {
            
            if (in_array($value['field'], ['academic_period_id'])) {
                    unset($cloneFields[$key]);
                    break;
            }
            
            if ($value['field'] == 'class_name') {
                $newFields[] = [
                    'key' => 'institution_name',
                    'field' => 'institution_name',
                    'type' => 'string',
                    'label' => __('Institution')
                ];

                $newFields[] = [
                    'key' => '',
                    'field' => 'region_code',
                    'type' => 'string',
                    'label' => 'Region Code'
                ];
        
                $newFields[] = [
                    'key' => '',
                    'field' => 'region_name',
                    'type' => 'string',
                    'label' => 'Region Name'
                ];
                
                $newFields[] = [
                    'key' => 'area_code',
                    'field' => 'area_code',
                    'type' => 'string',
                    'label' => __('District Code')
                ];

                $newFields[] = [
                    'key' => 'area_name',
                    'field' => 'area_name',
                    'type' => 'string',
                    'label' => __('District Name')
                ];
                
                $newFields[] = [
                    'key' => 'InstitutionClasses.name',
                    'field' => 'class_name',
                    'type' => 'string',
                    'label' => __('Institution Class')
                ];
                
                $newFields[] = [
                    'key' => 'InstitutionSubjects.name',
                    'field' => 'name',
                    'type' => 'string',
                    'label' => __('Subject Name')
                ];
                
                $newFields[] = [
                    'key' => 'staff_name',
                    'field' => 'staff_name',
                    'type' => 'string',
                    'label' => __('Subject Teacher')
                ];
                
                $newFields[] = [
                    'key' => 'InstitutionSubjects.no_of_seats',
                    'field' => 'no_of_seats',
                    'type' => 'integer',
                    'label' => __('Number of seats')
                ];
                
                $newFields[] = [
                    'key' => 'InstitutionSubjects.total_male_students',
                    'field' => 'total_male_students',
                    'type' => 'integer',
                    'label' => __('Male students')
                ];
                
                $newFields[] = [
                    'key' => 'InstitutionSubjects.total_female_students',
                    'field' => 'total_female_students',
                    'type' => 'integer',
                    'label' => __('Female students')
                ];
                
                $newFields[] = [
                    'key' => 'total_students',
                    'field' => 'total_students',
                    'type' => 'integer',
                    'label' => __('Total students')
                ];

            }            
        }
        
        $fields->exchangeArray($newFields); 
    }
}
