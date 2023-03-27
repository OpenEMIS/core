<?php
namespace Staff\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Utility\Inflector;
use App\Model\Table\ControllerActionTable;

//POCOR-6673
class StaffCurricularsTable extends ControllerActionTable {

	public function initialize(array $config) 
	{
		$this->table('institution_curricular_staff');
		$this->belongsTo('InstitutionCurriculars', ['className' => 'Institution.InstitutionCurriculars']);
		parent::initialize($config);
		$this->toggle('view', true);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
        $this->toggle('add', false);
        $this->toggle('search', true);
	}

	public function implementedEvents()
    {
        $events = parent::implementedEvents();
        return $events;
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
       if ($field == 'total_male_students') {
            return  __('Male Students');
        } else if ($field == 'total_female_students') {
            return  __('Female Students');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function beforeAction(Event $event, ArrayObject $extra) {
        $this->setupTabElements();
    }

    private function setupTabElements() {
        $options['type'] = 'staff';
        $tabElements = $this->controller->getCareerTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }
    

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $staffId = $this->Session->read('Staff.Staff.id');
        if (!empty($staffId)) {
            $staffId = $this->Session->read('Staff.Staff.id');
        } else {
            $staffId =$this->Session->read('Auth.User.id');
        }

        $institutionId = $this->Session->read('Institution.Institutions.id');
        $InstitutionCurriculars = TableRegistry::get('institution_curriculars');
        $curricular_types = TableRegistry::get('curricular_types');
        $academicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        if($this->controller->name == 'Profiles'){
            $query
                ->select([
                            $this->aliasField('id'),
                            'academic_period_id'=>$academicPeriods->aliasField('name'),
                            'type'=>$curricular_types->aliasField('name'),
                            'category'=>$InstitutionCurriculars->aliasField('category'),
                    ])
                ->LeftJoin([$InstitutionCurriculars->alias() => $InstitutionCurriculars->table()],
                        [$InstitutionCurriculars->aliasField('id').' = ' . $this->aliasField('institution_curricular_id')
                    ])
                ->LeftJoin([$academicPeriods->alias() => $academicPeriods->table()],
                        [$academicPeriods->aliasField('id').' = ' . $InstitutionCurriculars->aliasField('academic_period_id')
                        ])
                ->LeftJoin([$curricular_types->alias() => $curricular_types->table()],
                        [$curricular_types->aliasField('id').' = ' . $InstitutionCurriculars->aliasField('curricular_type_id')
                ])->where([$this->aliasField('staff_id')=>$staffId]);
        }else{

            $query
                ->select([
                            $this->aliasField('id'),
                            'academic_period_id'=>$academicPeriods->aliasField('name'),
                            'type'=>$curricular_types->aliasField('name'),
                            'category'=>$InstitutionCurriculars->aliasField('category'),
                    ])
                ->LeftJoin([$InstitutionCurriculars->alias() => $InstitutionCurriculars->table()],
                        [$InstitutionCurriculars->aliasField('id').' = ' . $this->aliasField('institution_curricular_id')
                    ])
                ->LeftJoin([$academicPeriods->alias() => $academicPeriods->table()],
                        [$academicPeriods->aliasField('id').' = ' . $InstitutionCurriculars->aliasField('academic_period_id')
                        ])
                ->LeftJoin([$curricular_types->alias() => $curricular_types->table()],
                        [$curricular_types->aliasField('id').' = ' . $InstitutionCurriculars->aliasField('curricular_type_id')
                ])->where([$this->aliasField('staff_id')=>$staffId,
                $InstitutionCurriculars->aliasField('institution_id')=>$institutionId]);
        }
            
        $this->field('institution_curricular_id', ['visible' => true]);
        $this->field('academic_period_id', ['visible' => true]);
        $this->field('type', ['visible' => true]);
        $this->field('category', ['visible' => true]);
       $this->field('total_male_students', ['visible' => ['index'=>true,'view' => false, 'edit' => false,'add'=>false]]);
        $this->field('total_female_students', ['visible' => ['index'=>true,'view' => false,'edit' => false,'add'=>false]]);
        $this->field('total_students', ['visible' => ['index'=>true,'view' => false,'edit' =>false,'add'=>false]]);
        $this->setFieldOrder([
        'academic_period_id', 'institution_curricular_id','category','type']);

        if ($this->controller->name == 'Profiles') {
            unset($settings['indexButtons']['view']);
        }

    }

    public function onGetCategory(Event $event, Entity $entity)
    {
        return $entity->category ? __('Curricular') : __('Extracurricular');
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        
        $this->field('academic_period_id', ['visible' => true]);
        $this->field('category', ['visible' => true]);
        $this->field('total_male_students', ['visible' => true]);
        $this->field('total_female_students', ['visible' => true]);
        $this->field('total_students', ['visible' => true]);
    }
	
}
