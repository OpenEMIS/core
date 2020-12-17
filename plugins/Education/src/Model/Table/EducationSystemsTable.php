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
class EducationSystemsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->hasMany('EducationLevels', ['className' => 'Education.EducationLevels']);
        $this->setDeleteStrategy('restrict');
    }

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

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
    	$this->setupFields($entity);
    }

    /*public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
    	if($this->request->action == 'CopySystems'){
    		echo "<pre>"; print_r($this->request);
			die;
    		$this->request->data['EducationSystems']['academic_period_id'] = $this->request->data['EducationSystems']['academic_period_id'];
        }
        
    }*/

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

    public function beforeSave(Event $event, Entity $entity, ArrayObject $data){
        $entity->academic_period_id = $this->request->data['EducationSystems']['academic_period_id'];
        $entity->modified_user_id = $this->Session->read('Auth.User.id');
        $entity->created_user_id = $this->Session->read('Auth.User.id');
    }

}
