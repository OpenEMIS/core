<?php
namespace Staff\Model\Table;

use ArrayObject;

use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\I18n\Date;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Http\ServerRequest;
use App\Model\Table\AppTable;
use App\Model\Table\ControllerActionTable;
use Cake\Filesystem\File;

use Laminas\Diactoros\UploadedFile;

class QualificationsTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('staff_qualifications');
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
        $this->behaviors()->get('ControllerAction')->setConfig(
            'actions.download.show',
            true
        );
        $this->addBehavior('Import.ImportLink', ['import_model' => 'ImportStaffQualifications']);
        $this->addBehavior('Excel', [
            'excludes' => ['staff_id'],
            'pages' => ['index'],
        ]);
        $this->addBehavior('User.UserTab', [
            'appliedAction' => ['StaffQualifications' =>
                ['education_field_of_study_id',
                    'qualification_title_id',
                    'qualification_country_id',
                    'staff_id'],
                'Qualifications' =>
                ['education_field_of_study_id',
                    'qualification_title_id',
                    'qualification_country_id',
                    'staff_id']
            ]
        ]);
        $this->addBehavior('Staff.StaffTab');
	}

    public function validationDefault(Validator $validator): Validator {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
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

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $queryString = $this->getQueryString();
        if(isset($queryString['staff_id']) && !empty($queryString['staff_id'])){
			$staffId = $queryString['staff_id'];
		}else{
			$staffId = $queryString['user_id'];
		}
        
        $this->field('staff_id', ['type' => 'hidden', 'value' => $staffId]);
    }

    public function indexBeforeAction(Event $event)
    {
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['type' => 'binary', 'visible' => false]);
        $this->field('gpa', ['visible' => false]);
        $this->field('qualification_country_id', ['visible' => false]);

        $this->field('qualification_level', ['type' => 'string','sort'=>['field'=>'QualificationLevels.name']]); //POCOR-6551
        $this->field('file_type', ['type' => 'string']);

        $this->setFieldOrder([
            'graduate_year', 'qualification_level', 'qualification_title_id', 'document_no', 'qualification_institution', 'file_type'
        ]);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['QualificationTitles.QualificationLevels']);
        // START: POCOR-6551 sort by level
        $sortList = ['QualificationLevels.name'];
        if (array_key_exists('sortWhitelist', $extra['options'])) {
            $sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
        }
        $extra['options']['sortWhitelist'] = $sortList;
        $query->order(['QualificationLevels.order'=>'ASC']);
        // END: POCOR-6551 sort by level

        // Start POCOR-5188
        if($this->request->getParam('controller') == 'Staff'){
            $is_manual_exist = $this->getManualUrl('Institutions','Qualifications','Staff - Professional');
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
            $is_manual_exist = $this->getManualUrl('Directory','Qualifications','Staff - Professional');
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

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        // if ($field == 'qualification_level') {
        //     return __('Level');
        // } elseif ($field == 'graduate_year') {
        //     return __('Graduate Year');
        // }elseif ($field == 'qualification_institution') {
        //     return __('Institution');
        // }elseif ($field == 'document_no') {
        //     return __('Document No');
        // }elseif ($field == 'qualification_title_id') {
        //     return __('Title');
        // }elseif ($field == 'industry_id') {
        //     return __('Industry');
        // }elseif ($field == 'education_field_of_study_id') {
        //     return __('Field of Study');
        // }elseif ($field == 'qualification_specialisations') {
        //     return __('Qualification Specialisations');
        // }elseif ($field == 'qualification_country_id') {
        //     return __('Qualification Country');
        // }elseif ($field == 'gpa') {
        //     return __('GPA');
        // }elseif ($field == 'created_user_id') {
        //     return __('Created By');
        // } else if ($field == 'created') {
        //     return  __('Created On');
        // }elseif ($field == 'modified_user_id') {
        //     return __('Last Modified By');
        // } else if ($field == 'modified') {
        //     return  __('Last Modified On');
        // } else if ($field == 'education_subjects') {
        //     return  __('Education Subject');
        // } else if ($field == 'file_content') {
        //     return  __('Attachment');
        // }else {
        //     return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        // }
        if ($field == 'qualification_level') {
            return __('Level');
        } elseif ($field == 'graduate_year') {
            return __('Graduate Year');
        }elseif ($field == 'qualification_institution') {
            return __('Institution');
        }elseif ($field == 'document_no') {
            return __('Document No');
        }elseif ($field == 'qualification_title_id') {
            return __('Title');
        }elseif ($field == 'industry_id') {
            return __('Industry');
        }elseif ($field == 'education_field_of_study_id') {
            return __('Field of Study');
        }elseif ($field == 'qualification_specialisations') {
            return __('Specialisations');
        }elseif ($field == 'qualification_country_id') {
            return __('Qualification Country');
        }elseif ($field == 'gpa') {
            return __('GPA');
        }elseif ($field == 'created_user_id') {
            return __('Created By');
        } else if ($field == 'created') {
            return  __('Created On');
        }elseif ($field == 'modified_user_id') {
            return __('Last Modified By');
        } else if ($field == 'modified') {
            return  __('Last Modified On');
        } else if ($field == 'education_subjects') {
            return  __('Subjects');
        } else if ($field == 'file_content') {
            return  __('Attachment');
        }else {
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

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        // determine if download button is shown
        $showFunc = function() use ($entity) {
            $filename = $entity->file_content;
            return !empty($filename);
        };
        $this->behaviors()->get('ControllerAction')->setConfig(
            'actions.download.show',
            $showFunc
        );
        $toolbarButtons =  $extra['toolbarButtons'];
        $filename = $entity->file_content;
        if( $toolbarButtons->offsetExists('download') && empty($filename)) {
            $extra['toolbarButtons']['download'] = false;
        }
        $this->setupFields($entity);
    }

    public function onUpdateFieldQualificationTitleId(Event $event, array $attr, $action, ServerRequest $request)
    {
        $queryParams = $request->getQueryParams();
        // Unset a specific parameter, for example 'title'
        unset($queryParams['title']);

        // Set the modified query parameters back to the request object
        $request = $request->withQueryParams($queryParams);
        $attr['onChangeReload'] = 'changeQualificationTitleId';
        return $attr;
    }

    public function addEditOnChangeQualificationTitleId(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;
        unset($request->getQuery['title']);

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->getAlias(), $request->getData())) {
                if (array_key_exists('qualification_title_id', $request->getData()[$this->getAlias()])) {
                    $QueryTitle = $request->getQuery('title');
                    $QueryTitle = $request->getData()[$this->getAlias()]['qualification_title_id'];
                }
            }
        }
    }

    public function onUpdateFieldQualificationLevel(Event $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add' || $action == 'edit') {

            if (array_key_exists('title', $this->request->getQuery())) {
                $qualificationTitle = $this->request->getQuery('title');
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

    public function onUpdateFieldGraduateYear(Event $event, array $attr, $action, ServerRequest $request)
    {
        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $lowestYear = $ConfigItems->value('lowest_year');
        if(!empty($lowestYear)) {
            $currentYear = new Date();
            $currentYear = $currentYear->format('Y');
            
            if (($action == 'add') || ($action == 'edit')) {
                for ($i=$currentYear;$i>=$lowestYear;$i--) {
                    $attr['options'][$i] = $i;
                }
            }
        }
        return $attr;
    }

    public function onUpdateFieldEducationFieldOfStudyId(Event $event, array $attr, $action, ServerRequest $request)
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

    public function onUpdateFieldQualificationSpecialisations(Event $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add' || $action == 'edit') {

            $requestData = $request->getData();
            $fieldOfStudyId = 0;

            if (array_key_exists($this->getAlias(), $requestData) && !empty($requestData[$this->getAlias()]['education_field_of_study_id'])) {
                $fieldOfStudyId = $requestData[$this->getAlias()]['education_field_of_study_id'];
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

    public function onUpdateFieldEducationSubjects(Event $event, array $attr, $action, ServerRequest $request)
    {
        switch ($action) {
            case 'edit': case 'add':
                $requestData = $request->getData();
                $fieldOfStudyId = 0;

                if (array_key_exists($this->getAlias(), $requestData) && !empty($requestData[$this->getAlias()]['education_field_of_study_id'])) {
                    $fieldOfStudyId = $requestData[$this->getAlias()]['education_field_of_study_id'];
                } else {
                    $entity = $attr['entity'];
                    if ($entity->has('education_field_of_study_id')) {
                        $fieldOfStudyId = $entity->education_field_of_study_id;
                    }
                }

                $subjectData = $this->EducationSubjects
                    ->find()
                    ->select([
                        $this->EducationSubjects->aliasField($this->EducationSubjects->getPrimaryKey()),
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
		$tabElements = $this->getProfessionalTabElements();
        $action = $this->getAlias();
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $action);
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

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $extraField[] = [
            'key'   => 'graduate_year',
            'field' => 'graduate_year',
            'type'  => 'integer',
            'label' => __('Graduate Year')
        ];

        $extraField[] = [
            'key'   => 'qualification_level',
            'field' => 'qualification_level',
            'type'  => 'string',
            'label' => __('Level')
        ];

        $extraField[] = [
            'key'   => 'qualification_title_id',
            'field' => 'qualification_title_id',
            'type'  => 'string',
            'label' => __('Title')
        ];

        $extraField[] = [
            'key'   => 'document_no',
            'field' => 'document_no',
            'type'  => 'string',
            'label' => __('Document No')
        ];

        $extraField[] = [
            'key'   => 'qualification_institution',
            'field' => 'qualification_institution',
            'type'  => 'string',
            'label' => __('Institution')
        ];

        $extraField[] = [
            'key'   => 'file_type',
            'field' => 'file_type',
            'type'  => 'string',
            'label' => __('File Type')
        ];

        $extraField[] = [
            'key'   => 'education_field_of_study_id',
            'field' => 'education_field_of_study_id',
            'type'  => 'string',
            'label' => __('Field Of Study')
        ];

        $fields->exchangeArray($extraField);
    }

    public function onExcelGetFileType(Event $event, Entity $entity)
    {
        return (!empty($entity->file_name))? $this->getFileTypeForView($entity->file_name): '';
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {

        $userId = $this->getUserID();
        if($userId == NULL){
            $userId = '';
        }
        $qualificationTitles = TableRegistry::get('FieldOption.QualificationTitles');
        $qualificationLevel = TableRegistry::get('FieldOption.QualificationLevels');

        $query
        ->select([
            'qualification_level' => 'QualificationLevels.name'
        ])
        ->leftJoin(
            [$qualificationTitles->getAlias() => $qualificationTitles->getTable()],[
                $qualificationTitles->aliasField('id = ').$this->aliasField('qualification_title_id')
            ])
        ->leftJoin(
            [$qualificationLevel->getAlias() => $qualificationLevel->getTable()],[
                $qualificationLevel->aliasField('id = ').$qualificationTitles->aliasField('qualification_level_id')
            ])
        ->where([
            //'staff_id =' .$staffUserId,
            $this->aliasField('staff_id') => $userId
        ])
        ->order(['QualificationLevels.order'=>'ASC']);  //POCOR-6551
        return $query;
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('education_field_of_study_id');
        $this->setFieldOrder([
            'qualification_title_id', 'qualification_level', 'education_field_of_study_id', 'qualification_specialisations', 'education_subjects', 'qualification_country_id', 'qualification_institution', 'document_no', 'graduate_year', 'gpa', 'file_content'
        ]);
    }
}
