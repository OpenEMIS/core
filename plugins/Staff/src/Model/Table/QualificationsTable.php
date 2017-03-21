<?php
namespace Staff\Model\Table;

use ArrayObject;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\I18n\Date;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use App\Model\Table\ControllerActionTable;

class QualificationsTable extends ControllerActionTable {
	public function initialize(array $config) {
		$this->table('staff_qualifications');
		parent::initialize($config);

		$this->addBehavior('ControllerAction.FileUpload', [
            // 'name' => 'file_name',
            // 'content' => 'file_content',
            'size' => '2MB',
            'contentEditable' => true,
            'allowable_file_types' => 'all',
            'useDefaultName' => true
        ]);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
		$this->belongsTo('QualificationTitles', ['className' => 'FieldOption.QualificationTitles']);
		$this->belongsTo('QualificationCountries', ['className' => 'FieldOption.Countries', 'foreignKey' => 'qualification_country_id']);

        $this->belongsToMany('EducationSubjects', [
            'className' => 'Education.EducationSubjects',
            'joinTable' => 'staff_qualifications_subjects',
            'foreignKey' => 'staff_qualification_id',
            'targetForeignKey' => 'education_subject_id',
            'through' => 'Staff.QualificationsSubjects',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

		// setting this up to be overridden in viewAfterAction(), this code is required
		$this->behaviors()->get('ControllerAction')->config(
			'actions.download.show',
			true
		);
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);

		return $validator
            ->requirePresence('qualification_level')
			->allowEmpty('graduate_year')
			->add('graduate_year', 'ruleNumeric', [
                    'rule' => ['numeric'],
                    'on' => function ($context) { //validate when only graduate_year is not empty
                        return !empty($context['data']['graduate_year']);
                    }
			])
			->notEmpty('qualification_institution', __('Please enter the institution'))
			->allowEmpty('file_content');
	}

	public function indexBeforeAction(Event $event) 
    {
		$this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['visible' => false]);
        $this->field('gpa', ['visible' => false]);
        $this->field('qualification_country_id', ['visible' => false]);

        $this->field('qualification_level', ['type' => 'string']);
        $this->field('file_type', ['type' => 'string']);

        $this->setFieldOrder([
            'graduate_year', 'qualification_level', 'qualification_title_id', 'document_no', 'qualification_institution', 'file_type'
        ]);
	}

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['QualificationTitles.QualificationLevels']);
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['EducationSubjects']);
        $query->contain(['QualificationTitles.QualificationLevels']);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
    	$this->setupFields($entity);
    }

	public function viewAfterAction(Event $event, Entity $entity) {
		// determine if download button is shown
		$showFunc = function() use ($entity) {
			$filename = $entity->file_content;
			return !empty($filename);
		};
		$this->behaviors()->get('ControllerAction')->config(
			'actions.download.show',
			$showFunc
		);

        $this->setupFields($entity);
	}

    public function onUpdateFieldQualificationTitleId(Event $event, array $attr, $action, Request $request)
    {
        pr($attr);
        $attr['onChangeReload'] = 'changeQualificationTitleId';

        return $attr;
    }

    public function addEditOnChangeQualificationTitleId(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;
        unset($request->query['title']);

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('qualification_title_id', $request->data[$this->alias()])) {
                    $request->query['title'] = $request->data[$this->alias()]['qualification_title_id'];
                }
            }
        }
    }

    public function onUpdateFieldQualificationLevel(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {

            if (array_key_exists('title', $this->request->query)) {
                $qualificationTitle = $this->request->query('title');
            } else {
                if (!empty($attr['entity'])) {
                    $qualificationTitle = $attr['entity']->qualification_title_id;
                }
            }

            if ($qualificationTitle) {
                $query = $this->QualificationTitles->find()
                        ->contain('QualificationLevels')
                        ->where([
                            $this->QualificationTitles->aliasField('id') => $qualificationTitle
                        ])
                        ->first();
                
                $attr['attr']['value'] = $query->qualification_level->name;
                $attr['value'] = $query->qualification_level_id;

                return $attr;
            }
        }
    }

	public function onUpdateFieldGraduateYear(Event $event, array $attr, $action, Request $request) 
    {
        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $lowestYear = $ConfigItems->value('lowest_year');

		$currentYear = new Date();
		$currentYear = $currentYear->format('Y');
        
		if (($action == 'add') || ($action == 'edit')) {
			for ($i=$currentYear;$i>=$lowestYear;$i--) {
				$attr['options'][$i] = $i;
			}
		}
		return $attr;
	}

    public function onUpdateFieldEducationSubjects(Event $event, array $attr, $action, Request $request) {
        switch ($action) {
          case 'edit': case 'add':
             $EducationSubjects = TableRegistry::get('Education.EducationSubjects');
             $subjectData = $EducationSubjects
                 ->find()
                 ->select([$EducationSubjects->aliasField($EducationSubjects->primaryKey()), $EducationSubjects->aliasField('name'), $EducationSubjects->aliasField('code')])
                 ->find('visible')
                 ->find('order')
                 ->toArray();

             $subjectOptions = [];
             foreach ($subjectData as $key => $value) {
                 $subjectOptions[$value->id] = $value->code . ' - ' . $value->name;
             }

             $attr['options'] = $subjectOptions;
             break;

         default:
             # code...
             break;
        }
        return $attr;
    }

	public function onGetFileType(Event $event, Entity $entity) {
		return (!empty($entity->file_name))? $this->getFileTypeForView($entity->file_name): '';;
	}

    public function onGetQualificationLevel(Event $event, Entity $entity) 
    {
        return $entity->qualification_title->qualification_level->name;
    }

	private function setupTabElements() {
		$tabElements = $this->controller->getProfessionalDevelopmentTabElements();
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->alias());
	}

	public function afterAction(Event $event, ArrayObject $extra) {
		$this->setupTabElements();
	}

    private function setupFields(Entity $entity)
    {
        $this->field('qualification_title_id', ['type' => 'select', 'entity' => $entity]);

        $this->field('qualification_level', ['type' => 'readonly', 'entity' => $entity]);

        $this->field('education_subjects', ['type' => 'chosenSelect', 'entity' => $entity]);

        $this->field('qualification_country_id', ['type' => 'select', 'entity' => $entity]);

        $this->field('graduate_year', ['type' => 'select', 'entity' => $entity]);

        $visible = ['index' => false, 'view' => false, 'add' => true, 'edit' => true];

        $this->field('file_name', [
            'type' => 'hidden', 
            'visible' => $visible
        ]);

        $this->field('file_content', [
            'type' => 'binary', 
            'visible' => $visible
        ]);

        $this->setFieldOrder([
            'qualification_title_id', 'qualification_level', 'education_subject_id', 'qualification_country_id', 'qualification_institution', 'document_no', 'graduate_year', 'gpa', 'file_content'
        ]);
    }
}
