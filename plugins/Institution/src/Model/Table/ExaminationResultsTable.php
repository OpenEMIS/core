<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\ResultSet;
use Cake\Event\Event;
use App\Model\Table\ControllerActionTable;

class ExaminationResultsTable extends ControllerActionTable
{
    private $fieldPrefix = 'examination_item_';
    private $examinationItems = null;

    public function initialize(array $config)
    {
        $this->table('examination_centres_examinations_students');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Examinations', ['className' => 'Examination.Examinations']);
        $this->belongsTo('ExaminationCentres', ['className' => 'Examination.ExaminationCentres']);
        $this->belongsTo('ExaminationCentresExaminations', [
            'className' => 'Examination.ExaminationCentresExaminations',
            'foreignKey' => ['examination_centre_id', 'examination_id']
        ]);
        $this->belongsToMany('ExaminationCentresExaminationsSubjects', [
            'className' => 'Examination.ExaminationCentresExaminationsSubjects',
            'joinTable' => 'examination_centres_examinations_subjects_students',
            'foreignKey' => ['examination_centre_id', 'examination_id', 'student_id'],
            'targetForeignKey' => ['examination_centre_id', 'examination_item_id'],
            'through' => 'Examination.ExaminationCentresExaminationsSubjectsStudents',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->hasMany('ExaminationCentreRoomsExaminationsStudents', [
            'className' => 'Examination.ExaminationCentreRoomsExaminationsStudents',
            'foreignKey' => ['examination_centre_id', 'examination_id', 'student_id'],
            'bindingKey' => ['examination_centre_id', 'examination_id', 'student_id'],
            'dependent' => true,
            'cascadeCallBacks' => true
        ]);

        $this->addBehavior('Examination.RegisteredStudents');

        // POCOR-6159
        $this->addBehavior('Excel', ['pages' => ['index']]);
        // POCOR-6159

        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.onGetFieldLabel'] = 'onGetFieldLabel';
        return $events;
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($this->startsWith($field, $this->fieldPrefix)) {
            $examinationItemId = str_replace($this->fieldPrefix, "", $field);

            if (!is_null($this->examinationItems) && array_key_exists($examinationItemId, $this->examinationItems)) {
                $examinationItemEntity = $this->examinationItems[$examinationItemId];
                $label = $examinationItemEntity->code;
                $label .= '&nbsp;&nbsp;<i class="fa fa-info-circle fa-lg fa-right icon-blue" tooltip-placement="top" uib-tooltip="'.$examinationItemEntity->name.'" tooltip-append-to-body="true" tooltip-class="tooltip-blue"></i>';

                return $label;
            }
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $extra['auto_contain'] = false;

        $query
            ->select([$this->aliasField('institution_id')])
            ->autoFields(true);

        // Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Institutions','Results','Examinations');       
        if(!empty($is_manual_exist)){
            $btnAttr = [
                'class' => 'btn btn-xs btn-default icon-big',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'target'=>'_blank'
            ];
    
            $helpBtn['url'] = $is_manual_exist['url'];
            $helpBtn['type'] = 'button';
            $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
            $helpBtn['attr'] = $btnAttr;
            $helpBtn['attr']['title'] = __('Help');
            $extra['toolbarButtons']['help'] = $helpBtn;
        }
		// End POCOR-5188
    }

    public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $this->field('date_of_birth', ['visible' => false]);
        $this->field('gender_id', ['visible' => false]);
        $this->field('total_mark', ['visible' => false]);

        $this->setupExaminationItemFields($query, $data, $extra);

        $this->setFieldOrder(['registration_number', 'openemis_no', 'student_id']);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        // Start: not applicable to unregister from Institutions > Examinations > Results
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        if (array_key_exists('unregister', $toolbarButtonsArray)) {
            unset($toolbarButtonsArray['unregister']);
        }
        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
        // End

        $this->field('total_mark', ['visible' => false]);
        $this->field('results', [
            'type' => 'custom_results', 'valueClass' => 'table-full-width'
        ]);

        $this->setFieldOrder(['registration_number']);
    }

    public function onGetCustomResultsElement(Event $event, $action, $entity, $attr, $options=[])
    {
        if ($action == 'view') {
            $tableHeaders = [__('Examination Item'), __('Subject'), __('Mark'), __('Weight'), __('Total Mark')];
            $tableCells = [];

            $gradingTypes = $this->getGradingTypes();
            $academicPeriodId = $entity->academic_period_id;
            $examinationId = $entity->examination_id;
            $institutionId = $entity->institution->id;
            $studentId = $entity->user->id;
            $studentExaminationResults = $this->getStudentExaminationResults($academicPeriodId, $examinationId, $institutionId, $studentId);

            foreach ($studentExaminationResults as $key => $obj) {
                $examItemObj = $obj->_matchingData['ExaminationItems'];
                $subjectObj = $obj->_matchingData['EducationSubjects'];
                $gradingOptionObj = $obj->_matchingData['ExaminationGradingOptions'];
                $gradingTypeId = $gradingOptionObj->examination_grading_type_id;
                $itemWeight = $examItemObj->weight;

                $resultType = "MARKS";
                $passMark = 0;
                if (!empty($gradingTypes) && array_key_exists($gradingTypeId, $gradingTypes)) {
                    $resultType = $gradingTypes[$gradingTypeId]->result_type;
                    $passMark = $gradingTypes[$gradingTypeId]->pass_mark;
                }

                $itemResult = '';
                $totalMark = '<i class="fa fa-minus"></i>';
                switch ($resultType) {
                    case 'MARKS':
                        $itemResult = number_format($obj->marks, 2);
                        $totalMark = number_format($obj->marks * $itemWeight, 2);
                        if ($itemResult < $passMark) {
                            $itemResult = '<span style="color:#CC5C5C;">' . $itemResult . '</span>';
                        }
                        break;
                    case 'GRADES':
                        $itemResult = $gradingOptionObj->code_name;
                        break;
                    default:
                        break;
                }

                $rowData = [];
                $rowData[] = $examItemObj->code_name;
                $rowData[] = $subjectObj->code_name;
                $rowData[] = $itemResult;
                $rowData[] = $itemWeight;
                $rowData[] = $totalMark;

                $tableCells[] = $rowData;
            }

            $attr['tableHeaders'] = $tableHeaders;
            $attr['tableCells'] = $tableCells;
        }

        return $event->subject()->renderElement('Institution.ExaminationResults/results', ['attr' => $attr]);
    }

    private function setupExaminationItemFields(Query $query, ResultSet $data, ArrayObject $extra)
    {
        if ($extra->offsetExists('selectedAcademicPeriod') && $extra->offsetExists('selectedExamination')) {
            $selectedAcademicPeriod = $extra['selectedAcademicPeriod'];
            $selectedExamination = $extra['selectedExamination'];

            if ($selectedExamination != '-1') {
                // Start: add each examination item as new columns
                $this->examinationItems = $this->getExaminationItems($selectedExamination);
                foreach ($this->examinationItems as $examItemKey => $examItemObj) {
                    $fieldName = $this->getFieldNameByExamItem($examItemObj);
                    $this->field($fieldName, [
                        'type' => 'string'
                    ]);
                }
                // End

                $this->setStudentExaminationResults($data);
            }
        }
    }

    private function getGradingTypes()
    {
        $gradingTypes = [];

        $ExaminationGradingTypes = TableRegistry::get('Examination.ExaminationGradingTypes');
        $gradingTypeResults = $ExaminationGradingTypes
            ->find()
            ->toArray();

        foreach ($gradingTypeResults as $gradingTypeKey => $gradingTypeObj) {
            $gradingTypes[$gradingTypeObj->id] = $gradingTypeObj;
        }

        return $gradingTypes;
    }

    private function getExaminationItems($selectedExamination)
    {
        $items = [];

        $ExaminationItems = TableRegistry::get('Examination.ExaminationItems');
        $EducationSubjects = TableRegistry::get('Education.EducationSubjects');
        $examinationItemResults = $ExaminationItems
            ->find()
            ->leftJoinWith('EducationSubjects')
            ->where([
                $ExaminationItems->aliasField('examination_id') => $selectedExamination,
                $ExaminationItems->aliasField('weight > ') => 0
            ])
            ->order([
                $EducationSubjects->aliasField('order'),
                $ExaminationItems->aliasField('code'),
                $ExaminationItems->aliasField('name')
            ])
            ->toArray();

        foreach ($examinationItemResults as $key => $obj) {
            $items[$obj->id] = $obj;
        }

        return $items;
    }

    private function getFieldNameByExamItem($examItemObj)
    {
        $fieldName = $this->fieldPrefix.$examItemObj->id;
        return $fieldName;
    }

    private function getStudentExaminationResults($academicPeriodId, $examinationId, $institutionId, $studentId)
    {
        $ExaminationItemResults = TableRegistry::get('Examination.ExaminationItemResults');
        $studentExaminationResults = $ExaminationItemResults
            ->find()
            ->select([
                $ExaminationItemResults->aliasField('id'),
                $ExaminationItemResults->aliasField('marks'),
                $ExaminationItemResults->aliasField('examination_grading_option_id'),
                $ExaminationItemResults->aliasField('student_id'),
                $ExaminationItemResults->aliasField('examination_id'),
                $ExaminationItemResults->aliasField('examination_item_id'),
                $ExaminationItemResults->aliasField('education_subject_id'),
                $ExaminationItemResults->aliasField('institution_id'),
                $ExaminationItemResults->aliasField('academic_period_id'),
                $ExaminationItemResults->Examinations->aliasField('code'),
                $ExaminationItemResults->Examinations->aliasField('name'),
                $ExaminationItemResults->Examinations->aliasField('education_grade_id'),
                $ExaminationItemResults->ExaminationItems->aliasField('id'),
                $ExaminationItemResults->ExaminationItems->aliasField('code'),
                $ExaminationItemResults->ExaminationItems->aliasField('name'),
                $ExaminationItemResults->ExaminationItems->aliasField('weight'),
                $ExaminationItemResults->EducationSubjects->aliasField('code'),
                $ExaminationItemResults->EducationSubjects->aliasField('name'),
                $ExaminationItemResults->ExaminationGradingOptions->aliasField('code'),
                $ExaminationItemResults->ExaminationGradingOptions->aliasField('name'),
                $ExaminationItemResults->ExaminationGradingOptions->aliasField('examination_grading_type_id'),
            ])
            ->contain('ExaminationGradingOptions') //POCOR-6879
            ->contain('ExaminationGradingOptions.ExaminationGradingTypes') //POCOR-6879
            ->innerJoinWith('Examinations')
            ->innerJoinWith('ExaminationItems')
            ->leftJoinWith('EducationSubjects')
           // ->innerJoinWith('ExaminationGradingOptions')
            ->where([
                $ExaminationItemResults->aliasField('academic_period_id') => $academicPeriodId,
                $ExaminationItemResults->aliasField('examination_id') => $examinationId,
                $ExaminationItemResults->aliasField('institution_id') => $institutionId,
                $ExaminationItemResults->aliasField('student_id') => $studentId,
                $ExaminationItemResults->ExaminationItems->aliasField('weight > ') => 0
            ])
            ->toArray();

        return $studentExaminationResults;
    }

    private function setStudentExaminationResults(ResultSet $data)
    {
        $gradingTypes = $this->getGradingTypes();
        $ExaminationItemResults = TableRegistry::get('Examination.ExaminationItemResults');

        foreach ($data as $examCentreStudentKey => $examCentreStudentObj) {
            $academicPeriodId = $examCentreStudentObj['academic_period_id'];
            $examinationId = $examCentreStudentObj['examination_id'];
            $institutionId = $examCentreStudentObj['institution_id'];
            $studentId = $examCentreStudentObj['student_id'];
            $studentExaminationResults = $this->getStudentExaminationResults($academicPeriodId, $examinationId, $institutionId, $studentId);

            foreach ($studentExaminationResults as $key => $itemResultObj) {
               
                $examItemObj = $itemResultObj->_matchingData['ExaminationItems'];
                //$gradingOptionObj = $itemResultObj->_matchingData['ExaminationGradingOptions'];
                $gradingOptionObj = $itemResultObj['examination_grading_option'];
                $gradingTypeId = $gradingOptionObj->examination_grading_type_id;
                $fieldName = $this->getFieldNameByExamItem($examItemObj);

                $resultType = "MARKS";
                $passMark = 0;
                if (!empty($gradingTypes) && array_key_exists($gradingTypeId, $gradingTypes)) {
                    $resultType = $gradingTypes[$gradingTypeId]->result_type;
                    $passMark = $gradingTypes[$gradingTypeId]->pass_mark;
                }

                $itemResult = '';
                switch ($resultType) {
                    case 'MARKS':
                        $itemResult = number_format($itemResultObj->marks, 2);
                        if ($itemResult < $passMark) {
                            $itemResult = '<span style="color:#CC5C5C;">' . $itemResult . '</span>';
                        }
                        break;
                    case 'MARK': //POCOR-6879
                        $itemResult = number_format($itemResultObj->marks, 2);
                        $itemResult = '<span style="color:#CC5C5;">' . $itemResult . '</span>';
                    break;
                    case 'GRADES':
                        $itemResult = $gradingOptionObj->code_name;
                        break;
                    default:
                        break;
                }

                $examCentreStudentObj->{$fieldName} = $itemResult;
            }
        }
    }

    // POCOR-6159 START
    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {   
        $academicPeriodId =  ($this->request->query['academic_period_id']) ? $this->request->query['academic_period_id'] : $this->AcademicPeriods->getCurrent();
        $examinationId = ($this->request->query['examination_id']) ? $this->request->query['examination_id'] : 0 ;
        $session = $this->request->session();
        $institutionId  = $session->read('Institution.Institutions.id');
        
        $students = TableRegistry::get('security_users');
        $IdentityTypes = TableRegistry::get('identity_types');
        $nationality = TableRegistry::get('nationalities');
        $examinations = TableRegistry::get('examinations');

        $query->select([
            $this->aliasField('id') , 
            $this->aliasField('registration_number') , 
            'first_name' => $students->aliasField('first_name'),
            'middle_name' => $students->aliasField('middle_name'),
            'third_name' => $students->aliasField('third_name'),
            'last_name' => $students->aliasField('last_name'),
            'openemis_no' => $students->aliasField('openemis_no'),
			'nationality_id' =>$nationality->aliasField('name'),
			'identity_type_id' =>$IdentityTypes->aliasField('name'),
			'identity_number' =>$students->aliasField('identity_number'),
			'education_grade_id' =>$examinations->aliasField('education_grade_id'),
            $this->aliasField('modified_user_id'),
            $this->aliasField('modified'), 
            $this->aliasField('created_user_id'),
            $this->aliasField('created')
        ])
        ->innerJoin([$students->alias() => $students->table()], [
            [$students->aliasField('id = '). $this->aliasField('student_id')],
        ])
        ->LeftJoin([$IdentityTypes->alias() => $IdentityTypes->table()], [
            [$IdentityTypes->aliasField('id = '). $students->aliasField('identity_type_id')],
        ])
        ->LeftJoin([$nationality->alias() => $nationality->table()], [
            [$nationality->aliasField('id = '). $students->aliasField('nationality_id')],
        ])
        ->LeftJoin([$examinations->alias() => $examinations->table()], [
            [$examinations->aliasField('id = '). $this->aliasField('examination_id')],
        ])
        ->where([
            'institution_id = ' .$institutionId,
            $this->aliasField('academic_period_id = ') .$academicPeriodId,
            $this->aliasField('examination_id = ') .$examinationId
        ]);

        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {
                $InstitutionStudents = TableRegistry::get('InstitutionStudents');
                $StudentStatuses = TableRegistry::get('Student.StudentStatuses');
                $statuses = $StudentStatuses->findCodeList();
                $repeatedStatus = $statuses['REPEATED'];

                $InstitutionStudentsCurrentData = $InstitutionStudents
                ->find()
                ->select([
                    'InstitutionStudents.id', 
                    'InstitutionStudents.student_status_id', 
                    'InstitutionStudents.previous_institution_student_id'
                ])
                ->where([
                    $InstitutionStudents->aliasField('student_id') => $row['student_id'],
                    $InstitutionStudents->aliasField('education_grade_id') => $row['education_grade_id'],
                    $InstitutionStudents->aliasField('student_status_id') => $repeatedStatus,
                ])
                ->order([$InstitutionStudents->aliasField('InstitutionStudents.student_status_id') => 'DESC'])
                ->autoFields(true)
                ->first();

                $StudentTransfers = TableRegistry::get('Institution.InstitutionStudentTransfers');
                $approvedStatuses = $StudentTransfers->getStudentTransferWorkflowStatuses('APPROVED');
                $institutionStudentTransfer = $StudentTransfers
                ->find()
                ->select([
                    $StudentTransfers->aliasField('id'),
                    $StudentTransfers->aliasField('student_id'),
                    $StudentTransfers->aliasField('previous_institution_id'),
                    $StudentTransfers->aliasField('previous_academic_period_id'),
                    $StudentTransfers->aliasField('status_id')
                ])
                ->where([
                    $StudentTransfers->aliasField('student_id') => $row['student_id'],
                    $StudentTransfers->aliasField('previous_institution_id') => $row['institution_id'],
                    $StudentTransfers->aliasField('previous_academic_period_id') => $row['academic_period_id'],
                    $StudentTransfers->aliasField('status_id IN') => $approvedStatuses
                ])
                ->order([$StudentTransfers->aliasField('status_id') => 'DESC'])
                ->autoFields(true)
                ->first();

                if($InstitutionStudentsCurrentData){
                    $student_status = "Yes";
                }else{
                    $student_status = 'No';
                }
                
                if ($institutionStudentTransfer) {
                    $transfer = 'Yes';
                } else {
                    $transfer = 'No';
                }

                $row['student'] = '';
                if($row->middle_name && $row->third_name){
                    $row['student'] = $row->first_name.' '.$row->middle_name.' '.$row->third_name.' '.$row->last_name;
                }else{
                    $row['student'] = $row->first_name.' '.$row->last_name;
                }

                $row['repeater_status'] = $student_status;
                $row['transfer_status'] = $transfer;
                return $row;
            });
        });
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {

        $extraField[] = [
            'key' => 'registration_number',
            'field' => 'registration_number',
            'type' => 'string',
            'label' => __('Registration Number')
        ];

        $extraField[] = [
            'key' => 'security_users.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('OpenEMIS ID')
        ];

        $extraField[] = [
            'key' => 'student',
            'field' => 'student',
            'type' => 'string',
            'label' => __('Student')
        ];

        $extraField[] = [
            'key' => 'nationality_id',
            'field' => 'nationality_id',
            'type' => 'string',
            'label' => __('Nationality')
        ];

        $extraField[] = [
            'key' => 'identity_type_id',
            'field' => 'identity_type_id',
            'type' => 'string',
            'label' => __('Identity Type')
        ];

        $extraField[] = [
            'key' => 'identity_number',
            'field' => 'identity_number',
            'type' => 'string',
            'label' => __('Identity Number')
        ];

        $extraField[] = [
            'key' => '',
            'field' => 'repeater_status',
            'type' => 'string',
            'label' => __('Repeated')
        ];
        
        $extraField[] = [
            'key' => '',
            'field' => 'transfer_status',
            'type' => 'string',
            'label' => __('Transferred')
        ];

        $fields->exchangeArray($extraField);
    }
    // POCOR-6159 END
}
