<?php
namespace Student\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\EventInterface;
use App\Model\Table\ControllerActionTable;
use Cake\ORM\TableRegistry;

class TextbooksTable extends ControllerActionTable {
    public function initialize(array $config): void
    {
        $this->setTable('institution_textbooks');
        parent::initialize($config);

        $this->belongsTo('MainTextbooks',       ['className' => 'Textbook.Textbooks', 'foreignKey' => ['textbook_id', 'academic_period_id']]);
        $this->belongsTo('TextbookStatuses',    ['className' => 'Textbook.TextbookStatuses']);
        $this->belongsTo('TextbookConditions',  ['className' => 'Textbook.TextbookConditions']);
        $this->belongsTo('Institutions',        ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods',     ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationSubjects',   ['className' => 'Education.EducationSubjects']);
        $this->belongsTo('EducationGrades',     ['className' => 'Education.EducationGrades']);
        $this->belongsTo('Users',               ['className' => 'User.Users', 'foreignKey' => 'security_user_id']); //POCOR-7603

        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
        $this->addBehavior('Institution.InstitutionTab');
    }

    public function implementedEvents(): array {
       $events = parent::implementedEvents();
        $events['ControllerAction.Model.getSearchableFields'] = ['callable' => 'getSearchableFields', 'priority' => 5];
        return $events;
    }

    public function getSearchableFields(EventInterface $event, ArrayObject $searchableFields) {
        $searchableFields[] = 'textbook_id';
    }

    public function beforeAction()
    {
        $this->field('academic_period_id', ['type' => 'select']);
        $this->field('institution_id', ['type' => 'select']);
        $this->field('student_id', ['visible' => false]);

        $this->setFieldOrder([
            'academic_period_id', 'institution_id', 'code', 'textbook_id', 'education_grade_id', 'education_subject_id', 'textbook_condition_id', 'textbook_status_id'
        ]);
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('comment', ['visible' => false]);
        $this->fields['textbook_id']['sort'] = ['field' => 'MainTextbooks.title'];
        $this->field('textbook_condition_id', ['visible' => false]);
        // Start POCOR-5188
		if($this->request->getParam('controller') == 'Students'){
			$is_manual_exist = $this->getManualUrl('Institutions','Textbooks','Students - Academic');       
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
		}elseif($this->request->getParam('controller') == 'Directories'){ 
			$is_manual_exist = $this->getManualUrl('Directory','Textbooks','Students - Academic');       
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

		}
		// End POCOR-5188
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $session = $this->request->getSession();
        $userData = $this->Session->read(); //# [POCOR-6548] Check if user data not found then add current login user data

        // POCOR-1893 Profile using loginId as studentId
        if ($this->controller->getName() == 'Profiles') {
            $session = $this->request->getSession();
            $sId = $this->getUserID();      
            if (!empty($sId)) {
                /**
                 * Need to add current login id as param when no data found in existing variable
                 * @author Anand Malvi <anand.malvi@mail.valuecoders.com>
                 * @ticket POCOR-6548
                 */
                //# START: [POCOR-6548] Check if user data not found then add current login user data
                if ( is_int($sId) ) {
                    $studentId = $sId;
                } else if ($sId == null || empty($sId) || $sId == '') {
                        $studentId = $userData['Auth']['User']['id'];
                } else {
                $studentId = $this->ControllerAction->paramsDecode($sId)['id'];
                }
                //# END: [POCOR-6548] Check if user data not found then add current login user data
            } else if($session->read('Auth.User.is_guardian') ==1) {
                /**
                 * Need to add current login id as param when no data found in existing variable
                 * @author Anand Malvi <anand.malvi@mail.valuecoders.com>
                 * @ticket POCOR-6548
                 */
                //# START: [POCOR-6548] Check if user data not found then add current login user data
                $studentId = $session->read('Student.ExaminationResults.student_id');
                if ( is_int($studentId) ) {
                    $studentId = $studentId;
                } else if ($studentId == null || empty($studentId) || $studentId == '') {
                    $studentId = $userData['Auth']['User']['id'];
                } else {
                 $studentId = $this->ControllerAction->paramsDecode($session->read('Student.ExaminationResults.student_id'))['id'];
                }
                //# END: [POCOR-6548] Check if user data not found then add current login user data
            } else {
                $studentId = $session->read('Auth.User.id');
            }
        } else {
            $studentId = $this->getStudentID();
            if(empty($studentId)){
                $studentId = $session->read('Student.Students.id');
            }
        }
        // end POCOR-1893

        $query->where([$this->aliasField('security_user_id IS') => $studentId]); //POCOR-7603

        $searchKey = $this->getSearchKey();
        if (strlen($searchKey)) {
            $query->matching('MainTextbooks'); //to enable search by textbook title
            $extra['OR'] = [
                $this->MainTextbooks->aliasField('title').' LIKE' => '%' . $searchKey . '%',
                $this->MainTextbooks->aliasField('code').' LIKE' => '%' . $searchKey . '%',
            ];
        }

        $sortList = ['code', 'MainTextbooks.title'];
        if (array_key_exists('sortWhitelist', $extra['options'])) {
            $sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
        }
        $extra['options']['sortWhitelist'] = $sortList;

        $extra['auto_contain_fields'] = ['Institutions' => ['code']];
    }

    public function afterAction(EventInterface $event, ArrayObject $extra)
    {
        $this->setupTabElements();
        //POCOR-8414 start
        $plugin = __($this->controller->getPlugin());
        if($plugin != 'Profile' && $plugin != 'GuardianNav'){
            //POCOR-9584: start - for view action pass[1] is the encoded record ID (only has 'id'),
            //   not the query string; always read student_id via getQueryString() which decodes pass[1] safely
            $queryString = $this->getQueryString();
            $userId = $queryString['user_id'] ?? $queryString['student_id'] ?? null;
            //POCOR-9584: end
            $Users = TableRegistry::getTableLocator()->get('User.Users');
            $result = $Users
                ->find()
                ->select(['first_name','last_name'])
                ->where(['id' =>  $userId])
                ->first();

            if ($result === null) { //POCOR-9584: guard against null result when userId is missing
                return;
            }
            $fullName = $result->first_name.' '.$result->last_name;
            try {
                
                $gettabName = 'Textbooks';
                $this->controller->set('contentHeader', $fullName . ' - ' . $gettabName);
                //$this->controller->set('contentHeader', $plugin);
            } catch (RecordNotFoundException $e) {
                Log::write('error', $e->getMessage());
            }
        }
    }

    private function setupTabElements()
    {
        $options['type'] = 'student';
        //$tabElements = $this->controller->getAcademicTabElements($options);
        $tabElements = $this->getAcademicTabElements($options);
        if($this->controller->getName() == 'GuardianNavs'|| $this->controller->getName() == 'Directories') {
			$tabElements = $this->controller->getAcademicTabElements($options);
		}
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->getAlias());
    }

    public function onGetTextbookId(EventInterface $event, Entity $entity)
    {
        return $entity->main_textbook->code_title;
    }

    public function onGetAcademicPeriodId(EventInterface $event, Entity $entity)
    {
        if (($this->action == 'view') || ($this->action == 'index')) {
            return $entity->academic_period->name;
        }
    }

    public function onGetInstitutionId(EventInterface $event, Entity $entity)
    {
        return $entity->institution->code_name;
    }


}
