<?php
namespace Staff\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Utility\Inflector;
use App\Model\Table\ControllerActionTable;

//POCOR-6673
class StaffCurricularsTable extends ControllerActionTable {

	public function initialize(array $config): void 
	{
		$this->setTable('institution_curricular_staff');
		$this->belongsTo('InstitutionCurriculars', ['className' => 'Institution.InstitutionCurriculars']);
		parent::initialize($config);
		$this->toggle('view', true);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
        $this->toggle('add', false);
        $this->toggle('search', true);
        $this->addBehavior('Institution.InstitutionTab');
        $this->addBehavior('Staff.StaffTab');
	}

	public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        return $events;
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize=true)
    {
       if ($field == 'total_male_students') {
            return  __('Male Students');
        } else if ($field == 'total_female_students') {
            return  __('Female Students');
        } else if ($field == 'category') {
            return __('Category');
        } else if ($field == 'academic_period_id') {
            return __('Academic Period');
        } else if ($field == 'institution_curricular_id') {
            return __('Institution Curricular');
        } else if ($field == 'curricular_type') {
            return __('Curricular Type');
        } else if ($field == 'institution_id') {
            return __('Institution');
        } else if ($field == 'total_students') {
            return __('Total Students');
        } else if ($field == 'total_female_students') {
            return  __('Female Students');
        } elseif ($field == 'modified_user_id') {
            return __('Modified By');
        } elseif ($field == 'modified') {
            return __('Modified On');
        } elseif ($field == 'created_user_id') {
            return __('Created By');
        } elseif ($field == 'created') {
            return __('Created On');
        }else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra) {
        //POCOR-8379 Starts use if condition only
        if($this->controller->getName() != 'Profiles'){
            //POCOR-8056
            $modelAlias = 'StaffCurriculars';
            $userType = 'StaffUser';
            $this->controller->changeUtilitiesHeader($this, $modelAlias, $userType);
            //POCOR-8056
        } //POCOR-8379 Ends
        $this->setupTabElements();
    }

    private function setupTabElements() {
        $options['type'] = 'staff';
        $tabElements = $this->getCareerTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->getAlias());
    }
    

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        //POCOR-8028 removed academic period
        $staffId = $this->getStaffID();
        if (!empty($staffId)) {
            $staffId = $this->getStaffID();
        } else {
            $staffId = $this->Session->read('Auth.User.id');
        }

        $institutionId = $this->getInstitutionID();
        $InstitutionCurriculars = TableRegistry::getTableLocator()->get('Institution.InstitutionCurriculars');
        $curricular_types = TableRegistry::getTableLocator()->get('FieldOption.CurricularTypes');
        if ($this->controller->getName() == 'Profiles') {
            $where = [$this->aliasField('staff_id') => $staffId];
        } else {
            $where = [$this->aliasField('staff_id') => $staffId,
                $InstitutionCurriculars->aliasField('institution_id') => $institutionId];
        }
        $query
            ->select([
                $this->aliasField('id'),
                'curricular_type' => $curricular_types->aliasField('name'),
                'category' => $InstitutionCurriculars->aliasField('category'),
                'total_male_students' => $InstitutionCurriculars->aliasField('total_male_students'),
                'total_female_students' => $InstitutionCurriculars->aliasField('total_female_students'),
            ])
            ->LeftJoin([$InstitutionCurriculars->getAlias() => $InstitutionCurriculars->getTable()],
                [$InstitutionCurriculars->aliasField('id') . ' = ' . $this->aliasField('institution_curricular_id')
                ])
            ->LeftJoin([$curricular_types->getAlias() => $curricular_types->getTable()],
                [$curricular_types->aliasField('id') . ' = ' . $InstitutionCurriculars->aliasField('curricular_type_id')
                ])->where($where);

        $this->field('institution_curricular_id', ['visible' => true]);
        $this->field('curricular_type', ['visible' => ['index' => true, 'view' => false, 'edit' => false, 'add' => false]]);
        $this->field('category', ['visible' => ['index' => false, 'view' => true, 'edit' => false, 'add' => false]]);
        $this->field('total_male_students', ['visible' => ['index' => true, 'view' => true, 'edit' => false, 'add' => false]]);
        $this->field('total_female_students', ['visible' => ['index' => true, 'view' => true, 'edit' => false, 'add' => false]]);
        $this->field('total_students', ['visible' => ['index' => true, 'view' => true, 'edit' => false, 'add' => false]]);
        $this->setFieldOrder([
            'institution_curricular_id',
            'category',
            'curricular_type',
            'total_male_students',
            'total_female_students',
            'total_students'
        ]);
        if ($this->controller->getName() == 'Profiles') {
            unset($settings['indexButtons']['view']);
        }

    }

    public function onGetCategory(EventInterface $event, Entity $entity)
    {
        
        return $entity['institution_curricular']['category'] ? __('Curricular') : __('Extracurricular');
    }

    public function onGetTotalMaleStudents(EventInterface $event, Entity $entity)
    {
        return $entity['institution_curricular']['total_male_students'];
    }

    public function onGetTotalFemaleStudents(EventInterface $event, Entity $entity)
    {
        return $entity['institution_curricular']['total_female_students'];
    }

    public function onGetTotalStudents(EventInterface $event, Entity $entity)
    {
        $total = $entity->total_male_students + $entity->total_female_students ;
        return $total;
    }

    public function viewBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('category', ['visible' => true]);
        $this->field('total_male_students', ['visible' => true]);
        $this->field('total_female_students', ['visible' => true]);
        $this->field('total_students', ['visible' => true]);
    }

	
}
