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

class QualificationsTable extends ControllerActionTable
{
	public function initialize(array $config)
    {
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
		$this->belongsTo('FieldOfStudies', ['className' => 'Education.EducationFieldOfStudies', 'foreignKey' => 'education_field_of_study_id']);
        
        $this->belongsToMany('EducationSubjects', [
            'className' => 'Education.EducationSubjects',
            'joinTable' => 'staff_qualifications_subjects',
            'foreignKey' => 'staff_qualification_id',
            'targetForeignKey' => 'education_subject_id',
            'through' => 'Staff.QualificationsSubjects',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->belongsToMany('QualificationSpecialisations', [
            'className' => 'FieldOption.QualificationSpecialisations',
            'joinTable' => 'staff_qualifications_specialisations',
            'foreignKey' => 'staff_qualification_id',
            'targetForeignKey' => 'qualification_specialisation_id',
            'through' => 'Staff.QualificationsSpecialisations',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

		// setting this up to be overridden in viewAfterAction(), this code is required
		$this->behaviors()->get('ControllerAction')->config(
			'actions.download.show',
			true
		);
        $this->addBehavior('Import.ImportLink', ['import_model' => 'ImportStaffQualifications']);
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);

		return $validator
            ->requirePresence('qualification_country_id')
            ->allowEmpty('graduate_year')
			->add('graduate_year', 'ruleNumeric', [
                    'rule' => ['numeric'],
                    'on' => function ($context) { //validate when only graduate_year is not empty
                        return !empty($context['data']['graduate_year']);
                    }
			])
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
    
    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'qualification_level') {
            return __('Level');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['EducationSubjects','QualificationSpecialisations']);
        $query->contain(['QualificationTitles.QualificationLevels']);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
    	$this->setupFields($entity);
    }

	public function viewAfterAction(Event $event, Entity $entity)
    {
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
        unset($request->query['title']);
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

            if (!empty($qualificationTitle)) {
                $query = $this->QualificationTitles->find()
                        ->contain('QualificationLevels')
                        ->where([
                            $this->QualificationTitles->aliasField('id') => $qualificationTitle
                        ])
                        ->first();

                $attr['attr']['value'] = $query->qualification_level->name;
                $attr['value'] = $query->qualification_level_id;
            } else {
                $attr['attr']['value'] = '';
                $attr['value'] = '';
            }

            return $attr;
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

    public function onUpdateFieldEducationFieldOfStudyId(Event $event, array $attr, $action, Request $request)
    {
        $fieldOfStudyOptions = $this->FieldOfStudies
            ->find('list')
            ->find('visible')
            ->find('order')
            ->toArray();

        if (!empty($fieldOfStudyOptions)) {
            $fieldOfStudyOptions = $fieldOfStudyOptions;
        } else {
            $fieldOfStudyOptions = ['' => $this->getMessage('general.select.noOptions')];
        }

        if ($action == 'add' || $action == 'edit') {
            $attr['options'] = $fieldOfStudyOptions;
            $attr['onChangeReload'] = true;
        }

        return $attr;
    }

    public function onUpdateFieldQualificationSpecialisations(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {

            $requestData = $request->data;
            $fieldOfStudyId = 0;

            if (array_key_exists($this->alias(), $requestData) && !empty($requestData[$this->alias()]['education_field_of_study_id'])) {
                $fieldOfStudyId = $requestData[$this->alias()]['education_field_of_study_id'];
            } else {
                $entity = $attr['entity'];
                if ($entity->has('education_field_of_study_id')) {
                    $fieldOfStudyId = $entity->education_field_of_study_id;
                }
            }

            $specialisationOptions = $this->QualificationSpecialisations
                                    ->find('list')
                                    ->find('visible')
                                    ->find('order')
                                    ->where([
                                        $this->QualificationSpecialisations->aliasField('education_field_of_study_id') => $fieldOfStudyId
                                    ])
                                    ->toArray();
            
            if (!empty($specialisationOptions)) {
                $attr['options'] = $specialisationOptions;
            } else {
                $attr['placeholder'] = $this->getMessage('general.select.noOptions');
            }
            
        }
        return $attr;
    }

    public function onUpdateFieldEducationSubjects(Event $event, array $attr, $action, Request $request)
    {
        switch ($action) {
            case 'edit': case 'add':
                $requestData = $request->data;
                $fieldOfStudyId = 0;

                if (array_key_exists($this->alias(), $requestData) && !empty($requestData[$this->alias()]['education_field_of_study_id'])) {
                    $fieldOfStudyId = $requestData[$this->alias()]['education_field_of_study_id'];
                } else {
                    $entity = $attr['entity'];
                    if ($entity->has('education_field_of_study_id')) {
                        $fieldOfStudyId = $entity->education_field_of_study_id;
                    }
                }

                $subjectData = $this->EducationSubjects
                    ->find()
                    ->select([
                        $this->EducationSubjects->aliasField($this->EducationSubjects->primaryKey()),
                        $this->EducationSubjects->aliasField('name'),
                        $this->EducationSubjects->aliasField('code')
                    ])
                    ->matching('FieldOfStudies', function ($q) use ($fieldOfStudyId) {
                        return $q->where([
                            'FieldOfStudies.id' => $fieldOfStudyId
                        ]);
                    })
                    ->find('visible')
                    ->find('order')
                    ->toArray();

                if (!empty($subjectData)) {
                    $subjectOptions = [];
                    foreach ($subjectData as $key => $value) {
                        $subjectOptions[$value->id] = $value->code_name;
                    }

                    $attr['options'] = $subjectOptions;
                } else {
                    $attr['placeholder'] = $this->getMessage('general.select.noOptions');
                }
            break;

            default:
            # code...
            break;
        }

        return $attr;
    }

	public function onGetFileType(Event $event, Entity $entity)
    {
		return (!empty($entity->file_name))? $this->getFileTypeForView($entity->file_name): '';;
	}

    public function onGetQualificationLevel(Event $event, Entity $entity)
    {
        return $entity->qualification_title->qualification_level->name;
    }

    public function onGetEducationFieldOfStudyId(Event $event, Entity $entity)
    {
        if ($entity->education_field_of_study_id == 0) {
            return __('Field of Study not configured');
        }
    }

    public function onGetEducationSubjects(Event $event, Entity $entity)
    {
        $value = '';

        $list = [];
        if ($entity->has('education_subjects')) {
            foreach ($entity->education_subjects as $key => $obj) {
                $list[] = $obj->code_name;
            }
        }

        if (!empty($list)) {
            $value = implode(', ', $list);
        }

        return $value;
    }

	private function setupTabElements()
    {
		$tabElements = $this->controller->getProfessionalTabElements();
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->alias());
	}

	public function afterAction(Event $event, ArrayObject $extra)
    {
		$this->setupTabElements();
	}

    private function setupFields(Entity $entity)
    {
        $this->field('qualification_title_id', ['type' => 'select', 'entity' => $entity]);

        $this->field('qualification_level', ['type' => 'readonly', 'entity' => $entity, 'attr' => ['label' => __('Level')]]);

        $this->field('education_field_of_study_id', ['type' => 'select', 'entity' => $entity]);

        $this->field('qualification_specialisations', [
            'type' => 'chosenSelect',
            'placeholder' => __('Select some specialisations'),
            'entity' => $entity
        ]);

        $this->field('education_subjects', [
            'type' => 'chosenSelect',
            'placeholder' => __('Select some subjects'),
            'entity' => $entity
        ]);

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
            'qualification_title_id', 'qualification_level', 'education_field_of_study_id', 'qualification_specialisations', 'education_subjects', 'qualification_country_id', 'qualification_institution', 'document_no', 'graduate_year', 'gpa', 'file_content'
        ]);
    }
}
