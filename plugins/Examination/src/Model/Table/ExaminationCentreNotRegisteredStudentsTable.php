<?php
namespace Examination\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;

class ExaminationCentreNotRegisteredStudentsTable extends ControllerActionTable {
    use OptionsTrait;

    public function initialize(array $config)
    {
        $this->table('institution_students');
        parent::initialize($config);
        $this->belongsTo('Users',           ['className' => 'Security.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('Institutions',    ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);

        $this->addBehavior('User.AdvancedNameSearch');
        $this->addBehavior('Examination.NotRegisteredStudents');

        $this->ExaminationCentreStudents = TableRegistry::get('Examination.ExaminationCentresExaminationsStudents');
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.onGetFieldLabel'] = 'onGetFieldLabel';
        return $events;
    }

    public function beforeAction(Event $event, ArrayObject $extra) {
        $extra['config']['selectedLink'] = ['controller' => 'Examinations', 'action' => 'RegisteredStudents'];
        $this->controller->getStudentsTab();

        // Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Administration','Not Registered Students','Examinations');       
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
}
