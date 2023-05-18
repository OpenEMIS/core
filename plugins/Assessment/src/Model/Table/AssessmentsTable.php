<?php
namespace Assessment\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Collection\Collection;
use Cake\Validation\Validator;
use Cake\View\Helper\UrlHelper;
use Cake\Routing\Router;
use App\Model\Traits\OptionsTrait;
use App\Model\Traits\HtmlTrait;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\MessagesTrait;
use Cake\Utility\Text;

class AssessmentsTable extends ControllerActionTable {
    use MessagesTrait;
    use HtmlTrait;
    use OptionsTrait;

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->hasMany('AssessmentPeriods', ['className' => 'Assessment.AssessmentPeriods', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('AssessmentItems', ['className' => 'Assessment.AssessmentItems', 'dependent' => true, 'cascadeCallbacks' => false]);

        $this->belongsToMany('GradingTypes', [
            'className' => 'Assessment.AssessmentGradingTypes',
            'joinTable' => 'assessment_items_grading_types',
            'foreignKey' => 'assessment_id',
            'targetForeignKey' => 'assessment_grading_type_id',
            'through' => 'Assessment.AssessmentItemsGradingTypes',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->addBehavior('ControllerAction.FileUpload', [
            'name' => 'excel_template_name',
            'content' => 'excel_template',
            'size' => '10MB',
            'contentEditable' => true,
            'allowable_file_types' => 'document',
            'useDefaultName' => true
        ]);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Results' => ['index', 'view'],
            'OpenEMIS_Classroom' => ['index']
        ]);
        $this->behaviors()->get('ControllerAction')->config(
            'actions.download.show',
            true
        );
        $this->behaviors()->get('Download')->config(
            'name',
            'excel_template_name'
        );
        $this->behaviors()->get('Download')->config(
            'content',
            'excel_template'
        );
        $this->setDeleteStrategy('restrict');
    }

    public function validationDefault(Validator $validator) {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('code', [
                'ruleUniqueCode' => [
                    'rule' => ['validateUnique', ['scope' => 'academic_period_id']],
                    'provider' => 'table'
                ]
            ])
            ->requirePresence('assessment_items')
            ->add('education_grade_id', [
                'ruleAssessmentExistByGradeAcademicPeriod' => [ //validate so only 1 assessment for each grade per academic period
                    'rule' => ['assessmentExistByGradeAcademicPeriod'],
                    'on' => function ($context) {
                        return $this->action == 'add';
                    }
                ]
            ])
            ->allowEmpty('excel_template');
    }

    public function validationUpdateAcademicTerm(Validator $validation)
    {
        return $validation
            ->add('assessment_periods', 'ruleNotEmptyAcademicTerm', [
                'rule'  => ['notEmptyAcademicTerm']
            ]);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('excel_template_name', ['visible' => false]);
        $this->field('excel_template', ['visible' => true]);

        $this->setFieldOrder(['code', 'name', 'description', 'excel_template_name', 'excel_template', 'academic_period_id', 'education_grade_id']);
    } 

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriodOptions($this->request->query('period')));

        $extra['selectedPeriod'] = $selectedPeriod;
        $extra['elements']['control'] = [
            'name' => 'Assessment.controls',
            'data' => [
                'periodOptions'=> $periodOptions,
                'selectedPeriod'=> $selectedPeriod
            ],
            'order' => 3
        ];

        $this->field('type', [
            'visible' => false
        ]);

        // Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Administration','Assessments','Assessments');       
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

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->where([$this->aliasField('academic_period_id') => $extra['selectedPeriod']]);
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['AssessmentItems.EducationSubjects']);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $assessmentItems = $entity->assessment_items;

        //this is to sort array based on certain value on subarray, in this case based on education order value
        usort($assessmentItems, function($a,$b){ return $a['education_subject']['order']-$b['education_subject']['order'];} );

        $entity->assessment_items = $assessmentItems;

        // determine if download button is shown
        $showFunc = function() use ($entity) {
            $filename = $entity->excel_template;
            return !empty($filename);
        };
        $this->behaviors()->get('ControllerAction')->config(
            'actions.download.show',
            $showFunc
        );
        // End

        $this->setupFields($entity);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.downloadTemplate'] = 'downloadTemplate';
        return $events;
    }

    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        // to set template download button
        $downloadUrl = $this->url('downloadTemplate');
        $this->controller->set('downloadOnClick', "javascript:window.location.href='". Router::url($downloadUrl) ."'");
    }    

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        
        if ($this->action == 'edit')
        {   $subejctitems = [];
           $subejctid = [];
            $assessmentItems = $entity->assessment_items;
            $gradeIds =  $entity['education_grade_id'];
            //this is to sort array based on certain value on subarray, in this case based on education order value
            usort($assessmentItems, function($a,$b){ return $a['education_subject']['order']-$b['education_subject']['order'];} );

            $entity->assessment_items = $assessmentItems;
            $entity->assessment_ids = $entity->id;
           
           $subejctids = [];
           foreach($assessmentItems as $value) {
                $subejctids[]= $value['education_subject_id'];
            }
            $EducationSubjects = TableRegistry::get('Education.EducationGradesSubjects');
            $subjectname = $EducationSubjects->find()
                        ->select(['id'=>'EducationSubjects.id',
                            'name'=>'EducationSubjects.name',
                            'code'=>'EducationSubjects.code'])
                        ->contain(['EducationSubjects'])
                        ->where([$EducationSubjects->aliasField('education_grade_id')=> $gradeIds])
                        ->order([$EducationSubjects->aliasField('created')])->toArray();//POCOR-7122
            
            foreach($subjectname as $value) {
                $subejctid[]= $value['id'];
                $subejctitems[] = $value['code'].'-'.$value['name'];
            }
            $results =  array_combine($subejctid, $subejctitems);
            $entity->assessment_subject = $results;
            $entity->assessment_subject = $results;
        }

        $this->setupFields($entity);
    }

    /**
    * POCOR-6780
    * add edit education subject based on assessment item
    */
    public function editAfterSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra)
    {
        if ($this->action == 'edit'){
            $currentTimeZone = date("Y-m-d H:i:s");
            $assessmentId = $entity['id'];
            if (array_key_exists($this->alias(), $requestData)) {
                if (array_key_exists('assessment_items', $requestData[$this->alias()])) {
                    foreach ($requestData[$this->alias()]['assessment_items'] as $key => $item) {
                        $subjectcheck = $item['education_subject_check'];
                        $subjectId = $item['education_subject_id'];
                        $weight = $item['weight'];
                        $classification = $item['classification'];
                        $checkid = $item['id_check'];
                        $ids = Text::uuid();
                        if($subjectcheck == 1){
                            $assessmentItems = TableRegistry::get('assessment_items');
                            $checkdata = $assessmentItems->find()->where([$assessmentItems->aliasField('assessment_id')=>$assessmentId,$assessmentItems->aliasField('education_subject_id')=>$subjectId])->toArray();
                            
                            if(isset($checkdata) && (!empty($checkdata)) && $checkid==null){

                                $ids = $checkdata[0]['id'];

                              $test =   $assessmentItems->updateAll(
                                ['weight' => $weight,'classification'=>$classification],    //field
                                [
                                 'id' => $ids, 
                                ] //condition
                                );
                            }else{
                                $data = [
                                    'id' => $ids ,
                                    'weight' => $weight,
                                    'classification' => $classification,
                                    'assessment_id' => $assessmentId,
                                    'education_subject_id' => $subjectId,
                                    'created_user_id' => 1,
                                    'created' => $currentTimeZone,
                                ];
                                $entity = $assessmentItems->newEntity($data);

                               $save =  $assessmentItems->save($entity);
                        } 
                            
                        }
                  
                      } 
                    }
            } else { //logic to capture error if no subject inside the grade.
                $errorMessage = $this->aliasField('noSubjects');
                $requestData['errorMessage'] = $errorMessage;
            }
        }
    }

    public function addBeforePatch(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra)
    {
        //patch data to handle fail save because of validation error.
        if (array_key_exists($this->alias(), $requestData)) {
            if (array_key_exists('assessment_items', $requestData[$this->alias()])) {
                $EducationSubjects = TableRegistry::get('Education.EducationSubjects');
                foreach ($requestData[$this->alias()]['assessment_items'] as $key => $item) {
                    $subjectId = $item['education_subject_id'];
                    $requestData[$this->alias()]['assessment_items'][$key]['education_subject'] = $EducationSubjects->get($subjectId);
                }
            } else { //logic to capture error if no subject inside the grade.
                $errorMessage = $this->aliasField('noSubjects');
                $requestData['errorMessage'] = $errorMessage;
            }
        }

    }

    public function addAfterSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra)
    {
        $errors = $entity->errors();
        if (!empty($errors)) {
            if (isset($requestData['errorMessage']) && !empty($requestData['errorMessage'])) {
                $this->Alert->error($requestData['errorMessage'], ['reset'=>true]);
            }
        }  
    }

    public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        $extra['excludedModels'] = [ //this will exclude checking during remove restrict
            $this->AssessmentItems->alias(),
            $this->GradingTypes->alias()
        ];
    }

    public function onGetExcelTemplate(Event $event, Entity $entity)
    {
        if ($entity->has('excel_template_name')) {
            return $entity->excel_template_name;
        }
    }

    public function onUpdateFieldExcelTemplate(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'index' || $action == 'view') {
            $attr['type'] = 'string';
        } else {
            // attr for template download button
            $attr['startWithOneLeftButton'] = 'download';
            $attr['type'] = 'binary';
        }
        return $attr;
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            if ($action == 'add') {

                list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriodOptions($this->request->query('period')));

                $attr['options'] = $periodOptions;
                $attr['default'] = $selectedPeriod;
				$attr['onChangeReload'] = true;

            } else {

                $attr['type'] = 'readonly';
                $attr['value'] = $attr['entity']->academic_period_id;
                $attr['attr']['value'] = $this->AcademicPeriods->get($attr['entity']->academic_period_id)->name;

            }
        }
        return $attr;
    }

    public function onUpdateFieldEducationProgrammeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'view') {
            $attr['visible'] = false;
        } else if ($action == 'add' || $action == 'edit') {
			$AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
			$academicPeriodId = !is_null($request->data($this->aliasField('academic_period_id'))) ? $request->data($this->aliasField('academic_period_id')) : $AcademicPeriod->getCurrent();

            $EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');
			
            if ($action == 'add') {
                $programmeOptions = $EducationProgrammes
                    ->find('list', ['keyField' => 'id', 'valueField' => 'cycle_programme_name'])
                    ->find('availableProgrammes')
                    ->contain(['EducationCycles.EducationLevels.EducationSystems'])
					->where(['EducationSystems.academic_period_id' => $academicPeriodId])
					->toArray();

                $attr['options'] = $programmeOptions;
                $attr['onChangeReload'] = 'changeEducationProgrammeId';

            } else {
                //since programme_id is not stored, then during edit need to get from grade
                $programmeId = $this->EducationGrades->get($attr['entity']->education_grade_id)->education_programme_id;
                $attr['type'] = 'readonly';
                $attr['value'] = $programmeId;
                $attr['attr']['value'] = $EducationProgrammes->get($programmeId)->name;
            }
        }
        return $attr;
    }

    public function addEditOnChangeEducationProgrammeId(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;
        unset($request->query['programme']);
        unset($data['Assessments']['assessment_items']);
        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('education_programme_id', $request->data[$this->alias()])) {
                    $request->query['programme'] = $request->data[$this->alias()]['education_programme_id'];
                }
            }
        }
    }

    public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {

            if ($action == 'add') {

                $selectedProgramme = $request->query('programme');
                $gradeOptions = [];
                if (!is_null($selectedProgramme)) {
                    $gradeOptions = $this->EducationGrades
                        ->find('list')
                        ->find('visible')
                        ->contain(['EducationProgrammes'])
                        ->where([$this->EducationGrades->aliasField('education_programme_id') => $selectedProgramme])
                        ->order(['EducationProgrammes.order' => 'ASC', $this->EducationGrades->aliasField('order') => 'ASC'])
                        ->toArray();
                }

                $attr['options'] = $gradeOptions;
                $attr['onChangeReload'] = 'changeEducationGrade';

            } else {

                $attr['type'] = 'readonly';
                $attr['attr']['value'] = $this->EducationGrades->get($attr['entity']->education_grade_id)->name;
            }
        }

        return $attr;
    }

    public function addEditOnChangeEducationGrade(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;
        unset($request->query['grade']);

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('education_grade_id', $request->data[$this->alias()])) {
                    $selectedGrade = $request->data[$this->alias()]['education_grade_id'];
                    $request->query['grade'] = $selectedGrade;

                    $assessmentItems = $this->AssessmentItems->populateAssessmentItemsArray($selectedGrade);
                    $data[$this->alias()]['assessment_items'] = $assessmentItems;
                }
            }
        }
    }

    public function setupFields(Entity $entity)
    {
        $this->field('type', [
            'type' => 'hidden',
            'value' => 2,
            'attr' => ['value' => 2]
        ]);
        $this->field('academic_period_id', [
            'type' => 'select',
            'select' => false,
            'entity' => $entity
        ]);
        $this->field('education_programme_id', [
            'type' => 'select',
            'entity' => $entity
        ]);
        $this->field('education_grade_id', [
            'type' => 'select',
            'entity' => $entity
        ]);
        $this->field('assessment_items', [
            'type' => 'element',
            'element' => 'Assessment.assessment_items'
        ]);

        $this->setFieldOrder([
            'code', 'name', 'description', 'academic_period_id', 'education_programme_id', 'education_grade_id', 'assessment_items'
        ]);
    }

    public function getAcademicPeriodOptions($querystringPeriod)
    {
        $periodOptions = $this->AcademicPeriods->getYearList();

        if ($querystringPeriod) {
            $selectedPeriod = $querystringPeriod;
        } else {
            $selectedPeriod = $this->AcademicPeriods->getCurrent();
        }

        return compact('periodOptions', 'selectedPeriod');
    }

    public function checkIfHasTemplate($assessmentId=0)
    {
        $hasTemplate = false;

        if (!empty($assessmentId)) {
            $entity = $this->get($assessmentId);
            $hasTemplate = !empty($entity->excel_template) ? true : false;
        }

        return $hasTemplate;
    }

    public function findByClass(Query $query, array $options)
    {
        if (array_key_exists('institution_class_id', $options) && !empty($options['institution_class_id'])) {
            $classId = $options['institution_class_id'];
            $InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
            $classResults = $InstitutionClasses
                ->find()
                ->contain(['ClassGrades'])
                ->where([$InstitutionClasses->aliasField('id') => $classId])
                ->all();

            if (!$classResults->isEmpty()) {
                $where = [];
                $classEntity = $classResults->first();
                $where[$this->aliasField('academic_period_id')] = $classEntity->academic_period_id;

                $gradeIds = [];
                foreach ($classEntity->class_grades as $key => $obj) {
                    $gradeIds[$obj->education_grade_id] = $obj->education_grade_id;
                }
                if (!empty($gradeIds)) {
                    $where[$this->aliasField('education_grade_id IN ')] = $gradeIds;
                }

                if (!empty($where)) {
                    $query->where([$where]);
                }
            }
        }
    }

    public function downloadTemplate()
    {
        $filename = 'assessment_report_template';
        $fileType = 'xlsx';
        $filepath = WWW_ROOT . 'export' . DS . 'customexcel'. DS . 'default_templates'. DS . $filename . '.' . $fileType;

        header("Pragma: public", true);
        header("Expires: 0"); // set expiration time
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header("Content-Disposition: attachment; filename=".basename($filepath));
        header("Content-Transfer-Encoding: binary");
        header("Content-Length: ".filesize($filepath));
        echo file_get_contents($filepath);
        exit(); //POCOR-7027
    }  

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {  
        if(isset($entity['assessment_items']) && $this->action == 'edit'){
                $entity['assessment_items'] = array();
        }
    }  
}
