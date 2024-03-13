<?php
namespace Staff\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;


class InstitutionAssociationStaffTable extends ControllerActionTable
{
    private $InstitutionAssociationStudent;
    public function initialize(array $config): void
    {
        $this->setTable('institution_associations');
        parent::initialize($config);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->hasMany('AssociationStaff', ['className' => 'Institution.InstitutionAssociationStaff', 'saveStrategy' => 'replace', 'foreignKey' => 'institution_association_id']);
        $this->toggle('view', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
        $this->toggle('add', false);
        $this->addBehavior('Institution.InstitutionTab');
        $this->addBehavior('Staff.StaffTab');
             
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        return $events;
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
       if ($field == 'total_male_students') {
            return  __('Male Students');
        } else if ($field == 'name') {
            return __('Name');
        } else if ($field == 'academic_period_id') {
            return __('Academic Period');
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
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
    
    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('total_students', []);
        $this->fields['code']['visible'] = false;
        $this->setFieldOrder(['academic_period_id','name','institution_id','total_male_students','total_female_students','total_students']);

        // Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Institutions','Associations','Staff - Career');       
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
        $staffId = $this->Session->read('Staff.Staff.id');
        if (!empty($staffId)) {
            $staffId = $this->Session->read('Staff.Staff.id');
        } else {
            $staffId =$this->Session->read('Auth.User.id');
        }
        $AssocoationStaff = TableRegistry::get('Institution.InstitutionAssociationStaff');
        $staffData = $AssocoationStaff->find()
                    ->select([$AssocoationStaff->aliasField('institution_association_id')])
                    ->where([$AssocoationStaff->aliasField('security_user_id') => $staffId])->toArray();
        
        $Ids = [];
        
        if (!empty($staffData)) {
            foreach ($staffData as $key => $value) {
                $Ids[] = $value->institution_association_id;
            }
        }
       
        $where = [];
        if (!empty($Ids)) {
          $where = [
                $this->aliasField('id IN') => $Ids,
            ];
        } 
        // $query
        // ->orWhere($where); // POCOR-7485
    }
  
    public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        // $options = ['type' => 'staff'];
        // $tabElements = $this->controller->getCareerTabElements($options);
        // $this->controller->set('tabElements', $tabElements);
        // $this->controller->set('selectedAction', 'Associations');
        $this->setupTabElements($extra);
    }

    private function setupTabElements($extra)
    {
        $options['type'] = 'staff';
        $userId = $this->getUserId();
        if (!is_null($userId)) {
            $options['user_id'] = $userId;
        }
        $tabElements = $this->getCareerTabElements($options);
        //echo '<pre>';print_r($extra);die;
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'StaffAssociations');
    }

     public function getUserId()
    {
        $userId = null;
        if (!is_null($this->request->getQuery('user_id'))) {
            $userId = $this->request->getQuery('user_id');
        } else {
            $session = $this->request->getSession();
            if ($session->check('Staff.Staff.id')) {
                $userId = $session->read('Staff.Staff.id');
            }
        }

        return $userId;
    }

    public function onGetTotalStudents(Event $event, Entity $entity)
    {

        return $entity->total_male_students + $entity->total_female_students;
        // if (!isset($this->InstitutionAssociationStudent)) {
        //     $this->InstitutionAssociationStudent = TableRegistry::get('Student.InstitutionAssociationStudent');
        // }
        // $count = $this->InstitutionAssociationStudent->getMaleCountByAssociations($entity->id) + $this->InstitutionAssociationStudent->getFemaleCountByAssociations($entity->id);
        // return $count.' ';
    }

}
