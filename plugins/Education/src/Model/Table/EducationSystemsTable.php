<?php
namespace Education\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;

class EducationSystemsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->hasMany('EducationLevels', ['className' => 'Education.EducationLevels']);
        $this->setDeleteStrategy('restrict');
    }
    //POCOR-5696 start
    public function setupFields(Entity $entity)
    {
    	if($this->request->action == 'CopySystems'){
    		$this->field('start_year', ['type' => 'select', 'entity' => $entity,'attr' => ['label' => __('From Academic Period')]]);
    		$this->field('education_system_id', ['type' => 'select', 'entity' => $entity,'attr' => ['label' => __('From Education System')]]);
    		$this->field('academic_period_id', ['type' => 'select', 'entity' => $entity,'attr' => ['label' => __('To Academic Period')]]);
    		$this->field('name');
    	}else{
    		$this->field('name');
			$this->field('academic_period_id', ['type' => 'select', 'entity' => $entity]);    		
    	}
        
    }
    
    public function validationDefault(Validator $validator)
    {
    	$validator = parent::validationDefault($validator);
    	if($this->request->action == 'CopySystems'){
	        $validator
	        		->requirePresence('start_year')
	        		->requirePresence('education_system_id')
	        		->requirePresence('academic_period_id');
	    }

	    return $validator;
    }
    //POCOR-5696 ends
    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
    	$this->setupFields($entity);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    //getting name of academic period
    public function getAcademicPeriodOptions($querystringPeriod)
    {
        $periodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);

        if ($querystringPeriod) {
            $selectedPeriod = $querystringPeriod;
        } else {
            $selectedPeriod = $this->AcademicPeriods->getCurrent();
        }

        return compact('periodOptions', 'selectedPeriod');
    }

    //added academic filter on systme listing
    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        if($this->request->action != 'CopySystems'){
        	$academicPeriodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
	        $selectedAcademicPeriod = !is_null($this->request->query('academic_period_id')) ? $this->request->query('academic_period_id') : $this->AcademicPeriods->getCurrent();
	        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
	        $where[$this->aliasField('academic_period_id')] = $selectedAcademicPeriod;
	        $extra['elements']['controls'] = ['name' => 'Education.controls', 'data' => [], 'options' => [], 'order' => 1];
	        $query->where($where);
        }	
    }
    //POCOR-5696 start
    public function indexBeforeAction(Event $event, ArrayObject $extra) {
        // from onUpdateToolbarButtons
        $btnAttr = [
            'class' => 'btn btn-xs btn-default icon-big',
            'data-toggle' => 'tooltip',
            'data-placement' => 'bottom',
            'escape' => false
        ];
 
        $extraButtons = [
            'copy' => [
                'Systems' => ['Educations', 'CopySystems', 'add'],
                'action' => 'CopySystems',
                'icon' => '<i class="fa fa-copy"></i>',
                'title' => __('Copy')
            ]
        ];

        foreach ($extraButtons as $key => $attr) {
            if ($this->AccessControl->check($attr['permission'])) {
                $button = [
                    'type' => 'button',
                    'attr' => $btnAttr,
                    'url' => [0 => 'add']
                ];
                $button['url']['action'] = $attr['action'];
                $button['attr']['title'] = $attr['title'];
                $button['label'] = $attr['icon'];

                $extra['toolbarButtons'][$key] = $button;
            }
        }
    }
    //POCOR-5696 ends

    //updating type of academic period
    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {	
    	if ($action == 'add' || $action == 'edit') {
            if ($action == 'add') {
                list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriodOptions($this->request->query('period')));

                $attr['options'] = $periodOptions;
                $attr['default'] = $selectedPeriod;

            } else {

                $attr['type'] = 'readonly';
                $attr['value'] = $attr['entity']->academic_period_id;
                $attr['attr']['value'] = $this->AcademicPeriods->get($attr['entity']->academic_period_id)->name;
            }
        }
        return $attr;
    }

    public function getSystemOptions($selectedAcademicPeriod)
    {
        $systemOptions = $this
            ->find('list')
            ->where([$this->aliasField('academic_period_id') => $selectedAcademicPeriod])
            ->toArray();

        return $systemOptions;
    }
    //POCOR-5696 start
    public function onUpdateFieldStartYear(Event $event, array $attr, $action, Request $request)
    {	
    	if($this->request->action == 'CopySystems'){
            if ($action == 'add') {
            	list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriodOptions($this->request->query('period')));

                $attr['options'] = $periodOptions;
                $attr['default'] = $selectedPeriod;
                $attr['onChangeReload'] = 'changeEducationSystemId';
            } 
        }
        return $attr;
    }

    public function addEditOnChangeEducationSystemId(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
    	
    	$request = $this->request;
        unset($request->query['education_system_id']);

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('education_system_id', $request->data[$this->alias()])) {
                    $request->query['education_system_id'] = $request->data[$this->alias()]['education_system_id'];
                }
            }
        }
    }

    public function onUpdateFieldEducationSystemId(Event $event, array $attr, $action, Request $request)
    {	

    	if($this->request->action == 'CopySystems'){
            if ($action == 'add') {
            	$selectedPeriod = '';
            	if( !empty($this->request->data['EducationSystems']['start_year']) ){
					$selectedPeriod =$this->request->data['EducationSystems']['start_year'];
            	}else{
            		$selectedPeriod = $this->AcademicPeriods->getCurrent();
            	}
            	$AcademicPeriods = TableRegistry::get('academic_periods');
            	$educationSytems = TableRegistry::get('education_systems');
                $educationSytemsList = $educationSytems
							    ->find()
							    ->select([	$educationSytems->aliasField('id'),
							    			$educationSytems->aliasField('academic_period_id'),
                                            $educationSytems->aliasField('name'),
                                            $AcademicPeriods->aliasField('start_year'),
                                            $AcademicPeriods->aliasField('end_year')
                                        ])
							    ->formatResults(function($results) {
							        return $results->combine(
							            'id',
							            function($row) {
							                return $row['name'] . ' ' . $row['academic_periods']['start_year'] . ' - ' . $row['academic_periods']['end_year'];
							            }
							        );
							    })
							    ->leftJoin(
                                    [$AcademicPeriods->alias() => $AcademicPeriods->table()], [
                                        $AcademicPeriods->aliasField('id = ') . $educationSytems->aliasField('academic_period_id')
                                    ]
                                )
							    ->where([$educationSytems->aliasField('academic_period_id') => $selectedPeriod])
                                ->toArray(); 
				
				$optionsArray = ['' => __('-- Select --')] + $educationSytemsList;
        		$attr['options'] = $optionsArray;
            } 
        }
        return $attr;
    }
    
    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
    	$session = $this->request->session();
    	if ($entity->isNew()) {
            $academic_period_id = $entity->academic_period_id;
            $this->updateAll(
                ['academic_period_id' => $academic_period_id],
                ['id' => $entity->id]
            );
        }

        //get all education level data from copied education level id
        $education_levels = TableRegistry::get('education_levels');
    	$educationLevelsData = $education_levels
							    ->find()
							    ->where([$education_levels->aliasField('education_system_id') => $entity->education_system_id])
							    ->All()
		                        ->toArray();

		if(!empty($educationLevelsData)){
			$level_data_arr = [];
			$cycle_data_arr = [];
			$prog_data_arr = [];
			$grade_data_arr = [];
			$sub_data_arr = [];
			$newLevelEntites = $newCycleEntites = [];
			foreach ($educationLevelsData as $level_key => $level_val) {
				//level data
				$level_data_arr[$level_key]['name'] = $level_val['name'];
				$level_data_arr[$level_key]['order'] = $level_val['order'];
				$level_data_arr[$level_key]['visible'] = $level_val['visible'];
				$level_data_arr[$level_key]['education_system_id'] = $entity->id;
				$level_data_arr[$level_key]['education_level_isced_id'] = $level_val['education_level_isced_id'];
				$level_data_arr[$level_key]['modified_user_id'] = '';
				$level_data_arr[$level_key]['modified'] = '';
				$level_data_arr[$level_key]['created_user_id'] = $session->read('Auth.User.id');
				$level_data_arr[$level_key]['created'] = date("Y-m-d H:i:s");
				//insert level data
				$newLevelEntites = $education_levels->newEntity($level_data_arr[$level_key]);
				$level_result = $education_levels->save($newLevelEntites);

				if(!empty($level_result)){
					//cycle data
					$education_cycles = TableRegistry::get('education_cycles');
			    	$educationCyclesData = $education_cycles
										    ->find()
										    ->where([$education_cycles->aliasField('education_level_id') => $level_val['id']])
										    ->All()
					                        ->toArray();
					if(!empty($educationCyclesData)){
						foreach ($educationCyclesData as $cycle_key => $cycle_val) {
							$cycle_data_arr[$level_key][$cycle_key]['name'] = $cycle_val['name'];
							$cycle_data_arr[$level_key][$cycle_key]['admission_age'] = $cycle_val['admission_age'];
							$cycle_data_arr[$level_key][$cycle_key]['order'] = $cycle_val['order'];
							$cycle_data_arr[$level_key][$cycle_key]['visible'] = $cycle_val['visible'];
							$cycle_data_arr[$level_key][$cycle_key]['education_level_id'] = $level_result->id;
							$cycle_data_arr[$level_key][$cycle_key]['modified_user_id'] = '';
							$cycle_data_arr[$level_key][$cycle_key]['modified'] = '';
							$cycle_data_arr[$level_key][$cycle_key]['created_user_id'] = $session->read('Auth.User.id');
							$cycle_data_arr[$level_key][$cycle_key]['created'] = date("Y-m-d H:i:s");
							//insert cycle data
							$newCycleEntites = $education_cycles->newEntity($cycle_data_arr[$level_key][$cycle_key]);
							$cycle_result = $education_cycles->save($newCycleEntites);

							if(!empty($cycle_result)){
								//programmes data
								$education_programmes = TableRegistry::get('education_programmes');
						    	$educationProgrammesData = $education_programmes
														    ->find()
														    ->where([$education_programmes->aliasField('education_cycle_id') => $cycle_val['id']])
														    ->All()
									                        ->toArray();
								if(!empty($educationProgrammesData)){
									foreach ($educationProgrammesData as $prog_key => $prog_val) {
										$prog_data_arr[$level_key][$cycle_key][$prog_key]['code'] = $prog_val['code'];
										$prog_data_arr[$level_key][$cycle_key][$prog_key]['name'] = $prog_val['name'];
										$prog_data_arr[$level_key][$cycle_key][$prog_key]['duration'] = $prog_val['duration'];
										$prog_data_arr[$level_key][$cycle_key][$prog_key]['order'] = $prog_val['order'];
										$prog_data_arr[$level_key][$cycle_key][$prog_key]['visible'] = $prog_val['visible'];
										$prog_data_arr[$level_key][$cycle_key][$prog_key]['education_field_of_study_id'] = $prog_val['education_field_of_study_id'];
										$prog_data_arr[$level_key][$cycle_key][$prog_key]['education_cycle_id'] = $cycle_result->id;
										$prog_data_arr[$level_key][$cycle_key][$prog_key]['education_certification_id'] = $prog_val['education_certification_id'];
										$prog_data_arr[$level_key][$cycle_key][$prog_key]['created_user_id'] = $session->read('Auth.User.id');
										$prog_data_arr[$level_key][$cycle_key][$prog_key]['created'] = date("Y-m-d H:i:s");
										//insert programmes data
										$newProgEntites = $education_programmes->newEntity($prog_data_arr[$level_key][$cycle_key][$prog_key]);
										$program_result = $education_programmes->save($newProgEntites);

										if(!empty($program_result)){
											//grades data
											$education_grades = TableRegistry::get('education_grades');
									    	$educationGradesData = $education_grades
																	    ->find()
																	    ->where([$education_grades->aliasField('education_programme_id') => $prog_val['id']])
																	    ->All()
												                        ->toArray();

											if(!empty($educationGradesData)){
												foreach ($educationGradesData as $grade_key => $grade_val) {
													$grade_data_arr[$level_key][$cycle_key][$prog_key][$grade_key]['code'] = $grade_val['code'];
													$grade_data_arr[$level_key][$cycle_key][$prog_key][$grade_key]['name'] = $grade_val['name'];
													$grade_data_arr[$level_key][$cycle_key][$prog_key][$grade_key]['admission_age'] = $grade_val['admission_age'];
													$grade_data_arr[$level_key][$cycle_key][$prog_key][$grade_key]['order'] = $grade_val['order'];
													$grade_data_arr[$level_key][$cycle_key][$prog_key][$grade_key]['visible'] = $grade_val['visible'];
													$grade_data_arr[$level_key][$cycle_key][$prog_key][$grade_key]['education_stage_id'] = $grade_val['education_stage_id'];
													$grade_data_arr[$level_key][$cycle_key][$prog_key][$grade_key]['education_programme_id'] = $program_result->id;
													$grade_data_arr[$level_key][$cycle_key][$prog_key][$grade_key]['created_user_id'] = $session->read('Auth.User.id');
													$grade_data_arr[$level_key][$cycle_key][$prog_key][$grade_key]['created'] = date("Y-m-d H:i:s");
													//insert grades data
													$newGradeEntites = $education_grades->newEntity($grade_data_arr[$level_key][$cycle_key][$prog_key][$grade_key]);
													$grade_result = $education_grades->save($newGradeEntites);

													if(!empty($grade_result)){
														//grades subject data
														$education_grades_subjects = TableRegistry::get('education_grades_subjects');
												    	$educationGradesSubjects = $education_grades_subjects
																				    ->find()
																				    ->where([$education_grades_subjects->aliasField('education_grade_id') => $grade_val['id']])
																				    ->All()
															                        ->toArray();

														if(!empty($educationGradesSubjects)){
															foreach ($educationGradesSubjects as $sub_key => $sub_val) {

														        $sub_data_arr[$level_key][$cycle_key][$prog_key][$grade_key][$sub_key]['id'] = Text::uuid();
																$sub_data_arr[$level_key][$cycle_key][$prog_key][$grade_key][$sub_key]['hours_required'] = $sub_val['hours_required'];
																$sub_data_arr[$level_key][$cycle_key][$prog_key][$grade_key][$sub_key]['visible'] = $sub_val['visible'];
																$sub_data_arr[$level_key][$cycle_key][$prog_key][$grade_key][$sub_key]['auto_allocation'] = $sub_val['auto_allocation'];
																$sub_data_arr[$level_key][$cycle_key][$prog_key][$grade_key][$sub_key]['education_grade_id'] = $grade_result->id;
																$sub_data_arr[$level_key][$cycle_key][$prog_key][$grade_key][$sub_key]['education_subject_id'] = $sub_val['education_subject_id'];
																$sub_data_arr[$level_key][$cycle_key][$prog_key][$grade_key][$sub_key]['created_user_id'] = $session->read('Auth.User.id');
																$sub_data_arr[$level_key][$cycle_key][$prog_key][$grade_key][$sub_key]['created'] = date("Y-m-d H:i:s");
																//insert grades subject data
																$newGradeSubEntites = $education_grades_subjects->newEntity($sub_data_arr[$level_key][$cycle_key][$prog_key][$grade_key][$sub_key]);

																$sub_grade_result = $education_grades_subjects->save($newGradeSubEntites);
															}
														}// if educationGradesSubjects
													}//grade ends
												}
											} // if educationGradesData
										}//program ends
									}
								} // if educationProgrammesData
							}//cycle ends	
						}
					} // if educationCyclesData
				}//level ends
			}
		} //if educationLevelsData            	           
    }
    //POCOR-5696 ends
}
