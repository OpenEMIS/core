<?php
namespace Student\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\Event\Event;
// use Cake\ORM\Query;
// use Cake\ORM\TableRegistry;
// use Cake\ORM\Table;

class AbsencesTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_site_student_absences');
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' =>'security_user_id']);
		$this->belongsTo('InstitutionSiteSections', ['className' => 'Institution.InstitutionSiteSections']);
		$this->belongsTo('StudentAbsenceReasons', ['className' => 'FieldOption.StudentAbsenceReasons']);
	}

	public function beforeAction($event) {
		$this->fields['student_absence_reason_id']['type'] = 'select';
	}

	public function indexBeforeAction(Event $event) {
		$query = $this->request->query;

		// $toolbarElements = [
  //           ['name' => 'Student.Absences/controls', 'data' => [], 'options' => []]
  //       ];
        // $this->controller->set('toolbarElements',$toolbarElements);

  //       $academicPeriodList = TableRegistry::get('AcademicPeriod.AcademicPeriods')->getList();
  //       $monthOptions = $this->generateMonthOptions();
		// $currentMonthId = $this->getCurrentMonthId();
  //       $this->controller->set('academicPeriodList', $academicPeriodList);
		// $this->controller->set('monthOptions', $monthOptions);

		// $selectedAcademicPeriod = isset($query['academic_period']) ? $query['academic_period'] : key($academicPeriodList);
		// $selectedMonth = isset($query['month']) ? $query['month'] : key($monthOptions);

		// $this->controller->set('selectedAcademicPeriod', $selectedAcademicPeriod);
		// $this->controller->set('selectedMonth', $selectedMonth);

		$this->fields['last_date_absent']['visible'] = false;
		$this->fields['full_day_absent']['visible'] = false;
		$this->fields['start_time_absent']['visible'] = false;
		$this->fields['end_time_absent']['visible'] = false;
		$this->fields['comment']['visible'] = false;
		$this->fields['security_user_id']['visible'] = false;
		$this->fields['institution_site_section_id']['visible'] = false;

		$this->ControllerAction->addField('days', []);
		$this->ControllerAction->addField('time', []);

		$order = 0;
		$this->ControllerAction->setFieldOrder('first_date_absent', $order++);
		$this->ControllerAction->setFieldOrder('days', $order++);
		$this->ControllerAction->setFieldOrder('time', $order++);
		$this->ControllerAction->setFieldOrder('student_absence_reason_id', $order++);
		$this->ControllerAction->setFieldOrder('absence_type', $order++);
	}

	// public function generateMonthOptions(){
	// 	$options = array();
	// 	for ($i = 1; $i <= 12; $i++)
	// 	{
	// 			$options[$i] = date("F", mktime(0, 0, 0, $i+1, 0, 0, 0));
	// 	}
		
	// 	return $options;
	// }

	// public function getCurrentMonthId(){
	// 	$options = $this->generateMonthOptions();
	// 	$currentMonth = date("F");
	// 	$monthId = 1;
	// 	foreach($options AS $id => $month){
	// 		if($currentMonth === $month){
	// 			$monthId = $id;
	// 			break;
	// 		}
	// 	}
		
	// 	return $monthId;
	// }
}