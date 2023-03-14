<?php
namespace User\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\Validation\Validator;
use Cake\Event\Event;

use App\Model\Table\ControllerActionTable;

class AwardsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('user_awards');
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        return $validator;
    }

    private function setupTabElements()
    {
        switch ($this->controller->name) {
            case 'Students':
                $tabElements = $this->controller->getAcademicTabElements();
                $this->controller->set('tabElements', $tabElements);
                $this->controller->set('selectedAction', $this->alias());
                break;
            /*POCOR-6267 starts*/
            case 'GuardianNavs':
                $tabElements = $this->controller->getAcademicTabElements();
                $this->controller->set('tabElements', $tabElements);
                $this->controller->set('selectedAction', $this->alias());
                break;
            /*POCOR-6267 ends*/
            case 'Staff':
                $tabElements = $this->controller->getProfessionalTabElements();
                $this->controller->set('tabElements', $tabElements);
                $this->controller->set('selectedAction', $this->alias());
                break;
            case 'Directories':
            case 'Profiles':
                $type = $this->request->query('type');
                $options['type'] = $type;
                $session = $this->request->session();
                $isStaff = $session->read('Auth.User.is_staff');
                if ($isStaff) {
                    $tabElements = $this->controller->getProfessionalTabElements($options);
                } else if ($this->action == 'index') {
                    $tabElements = $this->controller->getAcademicTabElements($options);
                } elseif ($type == 'student') {
                    $tabElements = $this->controller->getAcademicTabElements($options);
                } else {
                    $tabElements = $this->controller->getProfessionalTabElements($options);
                }

                $this->controller->set('tabElements', $tabElements);
                $this->controller->set('selectedAction', $this->alias());
                break;
        }
    }

    //Function Uncommented for ask POCOR-6267
    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
	{
		$session = $this->request->session();
		if ($this->controller->name == 'Profiles') {
			if ($session->read('Auth.User.is_guardian') == 1) {
				$sId = $session->read('Student.ExaminationResults.student_id');
			}else {
				$sId = $session->read('Student.Students.id');
			}
			if (!empty($sId)) {
				$studentId = $this->ControllerAction->paramsDecode($sId)['id'];
			} else {
				$studentId = $session->read('Auth.User.id');
			}
		} else {
				$studentId = $session->read('Student.Students.id');
		}

        $query->where([$this->aliasField('security_user_id') => $studentId]);
                
        // Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Guardian','Awards','Students - Academic');       
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

    public function afterAction(Event $event, ArrayObject $extra)
    {
        $this->setupTabElements();
    }
}
