<?php
namespace Examination\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Traits\OptionsTrait;
use App\Model\Traits\HtmlTrait;
use ArrayObject;
use Cake\Validation\Validator;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;
use Cake\Log\Log;

class ExaminationCentresTable extends ControllerActionTable {
    use OptionsTrait;
    use HtmlTrait;

    private $examCentreName = null;

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->addBehavior('Area.Areapicker');
        $this->belongsTo('Examinations', ['className' => 'Examination.Examinations']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('Areas', ['className' => 'Area.Areas']);
        $this->hasMany('ExaminationCentreSpecialNeeds', ['className' => 'Examination.ExaminationCentreSpecialNeeds', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ExaminationCentreRooms', ['className' => 'Examination.ExaminationCentreRooms', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ExaminationItemResults', ['className' => 'Examination.ExaminationItemResults', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->belongsToMany('LinkedInstitutions', [
            'className' => 'Institution.Institutions',
            'joinTable' => 'examination_centres_institutions',
            'foreignKey' => 'examination_centre_id',
            'targetForeignKey' => 'institution_id',
            'through' => 'Examination.ExaminationCentresInstitutions',
            'dependent' => true
        ]);
        $this->belongsToMany('Invigilators', [
            'className' => 'User.Users',
            'joinTable' => 'examination_centres_invigilators',
            'foreignKey' => 'examination_centre_id',
            'targetForeignKey' => 'invigilator_id',
            'through' => 'Examination.ExaminationCentresInvigilators',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->belongsToMany('ExaminationItems', [
            'className' => 'Examination.ExaminationItems',
            'joinTable' => 'examination_centre_subjects',
            'foreignKey' => 'examination_centre_id',
            'targetForeignKey' => 'examination_item_id',
            'through' => 'Examination.ExaminationCentreSubjects',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->belongsToMany('Students', [
            'className' => 'User.Users',
            'joinTable' => 'examination_centre_students',
            'foreignKey' => 'examination_centre_id',
            'targetForeignKey' => 'student_id',
            'through' => 'Examination.ExaminationCentreStudents',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->addBehavior('Restful.RestfulAccessControl', [
            'ExamResults' => ['view']
        ]);

        $this->addBehavior('OpenEmis.Section');
        $this->setDeleteStrategy('restrict');

        $this->addBehavior('Import.ImportLink', ['import_model' => 'ImportExaminationCentreRooms']);
    }

    public function implementedEvents() {
        $events = parent::implementedEvents();
        $newEvent = [
            'ControllerAction.Model.ajaxInvigilatorAutocomplete' => 'ajaxInvigilatorAutocomplete',
            'Model.Institutions.afterSave' => 'institutionAfterSave',
        ];
        $events = array_merge($events, $newEvent);
        return $events;
    }

    public function institutionAfterSave(Event $event, Entity $entity)
    {
        if (!$entity->isNew()) {
            $this->updateAll([
                'code' => $entity->code,
                'name' => $entity->name,
                'area_id' => $entity->area_id,
                'address' => $entity->address,
                'postal_code' => $entity->postal_code,
                'contact_person' => $entity->contact_person,
                'telephone' => $entity->telephone,
                'fax' => $entity->fax,
                'email' => $entity->email
            ],
            [
                'institution_id' => $entity->id
            ]);
        }

    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        $validator
            ->requirePresence('create_as', 'create')
            ->requirePresence('academic_period_id')
            ->requirePresence('examination_id')
            ->add('examination_id', 'ruleNoRunningSystemProcess', [
                'rule' => ['checkNoRunningSystemProcess', 'AddAllInstitutionsExamCentre'],
                'provider' => 'table',
                'on' => function ($context) {
                    $createAs = (array_key_exists('create_as', $context['data']))? $context['data']['create_as']: null;
                    return ($createAs == 'existing');
                }
            ])
            ->requirePresence('code')
            ->requirePresence('name')
            ->add('code', 'ruleUnique', [
                'rule' => ['validateUnique', ['scope' => 'examination_id']],
                'provider' => 'table'
            ])
            ->requirePresence('institutions', 'create')
            ->allowEmpty('postal_code')
            ->add('postal_code', 'ruleCustomPostalCode', [
                'rule' => ['validateCustomPattern', 'postal_code'],
                'provider' => 'table',
                'last' => true
            ])
            ;

        return $validator;
    }

    public function validationNoInstitutions(Validator $validator) {
        $validator = $this->validationDefault($validator);
        $validator = $validator
            ->requirePresence('institutions', false);
        return $validator;
    }

    public function validationInstitutions(Validator $validator) {
        $validator = $this->validationDefault($validator);
        $validator = $validator
            ->requirePresence('code', false)
            ->remove('code')
            ->requirePresence('name', false);
        return $validator;
    }

    public function validationAllInstitutions(Validator $validator) {
        $validator = $this->validationDefault($validator);
        $validator = $validator
            ->requirePresence('institutions', false)
            ->remove('institutions')
            ->requirePresence('code', false)
            ->remove('code')
            ->requirePresence('name', false);
        return $validator;
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
        $selectedAcademicPeriod = !is_null($this->request->query('academic_period_id')) ? $this->request->query('academic_period_id') : $this->AcademicPeriods->getCurrent();
        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
        $where[$this->aliasField('academic_period_id')] = $selectedAcademicPeriod;
        $extra['elements']['controls'] = ['name' => 'Examination.controls', 'data' => [], 'options' => [], 'order' => 1];
        // Examination
        $examinationOptions = $this->getExaminationOptions($selectedAcademicPeriod);
        $examinationOptions = ['-1' => '-- '.__('Select Examination').' --'] + $examinationOptions;
        $selectedExamination = !is_null($this->request->query('examination_id')) ? $this->request->query('examination_id') : -1;
        $this->controller->set(compact('examinationOptions', 'selectedExamination'));
        if ($selectedExamination != -1) {
           $where[$this->aliasField('examination_id')] = $selectedExamination;
        }

        $query->where($where);
    }

    public function onUpdateIncludes(Event $event, ArrayObject $includes, $action)
    {
        if ($action == 'edit') {
            $includes['autocomplete'] = [
                'include' => true,
                'css' => ['OpenEmis.../plugins/autocomplete/css/autocomplete'],
                'js' => ['OpenEmis.../plugins/autocomplete/js/autocomplete']
            ];
        }
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        $params = ['examination_centre_id' => $entity->id];
        if (isset($buttons['view']['url'])) {
            $buttons['view']['url'] = $this->ControllerAction->setQueryString($buttons['view']['url'], $params);
        }

        if (isset($buttons['edit']['url'])) {
            $buttons['edit']['url'] = $this->ControllerAction->setQueryString($buttons['edit']['url'], $params);
        }

        return $buttons;
    }

    private function getExaminationOptions($selectedAcademicPeriod) {
        $examinationOptions = $this->Examinations
            ->find('list')
            ->where([$this->Examinations->aliasField('academic_period_id') => $selectedAcademicPeriod])
            ->toArray();

        return $examinationOptions;
    }

    public function ajaxInvigilatorAutocomplete()
    {
        $this->controller->autoRender = false;
        $this->autoRender = false;

        if ($this->request->is(['ajax'])) {
            $term = $this->request->query['term'];
            $search = sprintf('%s%%', $term);
            $examinationId = $this->paramsDecode($this->paramsPass(0))['examination_id'];
            $data = [];
            $Users = $this->Invigilators;
            $list = $Users
                ->find()
                ->leftJoin(['ExaminationCentresInvigilators' => 'examination_centres_invigilators'], [
                    'ExaminationCentresInvigilators.invigilator_id = '.$Users->aliasField('id'),
                    'ExaminationCentresInvigilators.examination_id' => $examinationId
                ])
                ->select([
                    $Users->aliasField('id'),
                    $Users->aliasField('openemis_no'),
                    $Users->aliasField('first_name'),
                    $Users->aliasField('middle_name'),
                    $Users->aliasField('third_name'),
                    $Users->aliasField('last_name'),
                    $Users->aliasField('preferred_name')
                ])
                ->where([
                    'ExaminationCentresInvigilators.invigilator_id IS NULL',
                    $Users->aliasField('is_student') => 0,
                    'OR' => [
                        $Users->aliasField('openemis_no LIKE ') => $search,
                        $Users->aliasField('first_name LIKE ') => $search,
                        $Users->aliasField('middle_name LIKE ') => $search,
                        $Users->aliasField('third_name LIKE ') => $search,
                        $Users->aliasField('last_name LIKE ') => $search
                    ]
                ])
                ->order([$Users->aliasField('first_name')])
                ->all();

            foreach($list as $obj) {
                $data[] = [
                    'label' => sprintf('%s - %s', $obj->openemis_no, $obj->name),
                    'value' => $obj->id
                ];
            }

            echo json_encode($data);
            die;
        }
    }

    public function findBySpecialNeeds(Query $query, array $options)
    {
        $selectedSpecialNeeds = $options['selectedSpecialNeeds'];
        $query
            ->select(['count' => $this->find()->func()->count('*')])
            ->matching('ExaminationCentreSpecialNeeds')
            ->where([
                $this->ExaminationCentreSpecialNeeds->aliasField('special_need_type_id IN') => $selectedSpecialNeeds
            ])
            ->group($this->aliasField('id'))
            ->having(['count =' => count($selectedSpecialNeeds)])
            ->autoFields(true);
        return $query;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        if ($this->action == 'view' || $this->action == 'edit') {
            $examCentreId = $this->ControllerAction->getQueryString('examination_centre_id');
            $this->request->params['pass'][1] = $this->paramsEncode(['id' => $examCentreId]);
            $extra['config']['selectedLink'] = ['controller' => 'Examinations', 'action' => 'ExamCentres', 'index'];
            $this->examCentreName = $this->get($examCentreId)->name;
        }
        $this->field('institution_id', ['visible' => false]);
        $this->field('name');
        $this->fields['area_id']['visible'] = false;
        $this->fields['code']['visible'] = false;
        $this->fields['address']['visible'] = false;
        $this->fields['postal_code']['visible'] = false;
        $this->fields['contact_person']['visible'] = false;
        $this->fields['telephone']['visible'] = false;
        $this->fields['fax']['visible'] = false;
        $this->fields['email']['visible'] = false;
        $this->fields['website']['visible'] = false;

    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query
            ->contain(['ExaminationItems.EducationSubjects'])
            ->contain(['ExaminationCentreSpecialNeeds.SpecialNeedTypes'])
            ->contain(['ExaminationCentreRooms.Students'])
            ->contain([
                'Examinations',
                'LinkedInstitutions',
                'Invigilators' => [
                    'sort' => ['Invigilators.first_name' => 'ASC', 'Invigilators.last_name' => 'ASC']
                ]
            ])
            ->matching('Examinations')
            ->matching('Areas')
            ->matching('AcademicPeriods');
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        // Set the header of the page
        $this->controller->set('contentHeader', $this->examCentreName. ' - ' .__('Overview'));

        $this->controller->getExamCentresTab();
    }

    public function editBeforeAction(Event $event, ArrayObject $extra)
    {
        // Set the header of the page
        $this->controller->set('contentHeader', $this->examCentreName. ' - ' .__('Overview'));

        $this->controller->getExamCentresTab();
    }

    public function editOnAddInvigilator(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $alias = $this->alias();
        $fieldKey = 'invigilators';

        if (empty($data[$this->alias()][$fieldKey])) {
            $data[$this->alias()][$fieldKey] = [];
        }

        if ($data->offsetExists($alias)) {
            if (array_key_exists('invigilator_id', $data[$alias]) && !empty($data[$alias]['invigilator_id'])) {
                $id = $data[$alias]['invigilator_id'];

                try {
                    $obj = $this->Invigilators->get($id);

                    $data[$alias][$fieldKey][] = [
                        'id' => $obj->id,
                        'openemis_no' => $obj->openemis_no,
                        'name' => $obj->name,
                        '_joinData' => ['academic_period_id' => $entity->academic_period_id, 'examination_id' => $entity->examination_id]
                    ];

                    $data[$alias]['invigilator_id'] = '';
                } catch (RecordNotFoundException $ex) {
                    Log::write('debug', __METHOD__ . ': Record not found for id: ' . $id);
                }
            }
        }
    }

    public function editBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $options['associated'][] = 'LinkedInstitutions._joinData';
        $options['associated'][] = 'Invigilators._joinData';
        $options['associated'][] = 'ExaminationCentreSpecialNeeds';

        if (!isset($data[$this->alias()]['invigilators'])) {
            $data[$this->alias()]['invigilators'] = [];
        }

        $data[$this->alias()]['linked_institutions'] = $this->processInstitutions($data);
    }

    public function editAfterSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        // manually delete hasMany examCentreSpecialNeeds data
        $fieldKey = 'examination_centre_special_needs';
        if (!array_key_exists($fieldKey, $data[$this->alias()])) {
            $data[$this->alias()][$fieldKey] = [];
        }

        $specialNeedIds = array_column($data[$this->alias()][$fieldKey], 'special_need_type_id');
        $originalSpecialNeeds = $entity->extractOriginal([$fieldKey])[$fieldKey];
        foreach ($originalSpecialNeeds as $key => $need) {
            if (!in_array($need['special_need_type_id'], $specialNeedIds)) {
                $this->ExaminationCentreSpecialNeeds->delete($need);
                unset($entity->examination_centre_special_needs[$key]);
            }
        }
    }

    public function processInstitutions(ArrayObject $data)
    {
        $institutions = [];
        if (isset($data[$this->alias()]['linked_institutions']['_ids']) && !empty($data[$this->alias()]['linked_institutions']['_ids'])) {
            foreach ($data[$this->alias()]['linked_institutions']['_ids'] as $key => $value) {
                $joinData = [
                    'examination_centre_id' => $data[$this->alias()]['id'],
                    'institution_id' => $value,
                    'examination_id' => $data[$this->alias()]['examination_id'],
                    'academic_period_id' => $data[$this->alias()]['academic_period_id']
                ];
                $institutions[] = [
                    'id' => $value,
                    '_joinData' => $joinData
                ];
            }
            unset($data[$this->alias()]['linked_institutions']);
        }

        return $institutions;
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        $this->fields['total_registered']['visible'] = false;
        $entity = $extra['entity'];
        $this->field('linked_institutions_section', ['type' => 'section', 'title' => __('Linked Institutions'), 'visible' => false]);
        $this->field('subjects_section', ['type' => 'section', 'title' => __('Subjects'), 'visible' => false]);
        $this->field('exam_centre_info_section', ['type' => 'section', 'title' => __('Examination Centre Information'), 'visible' => false]);
        $this->field('invigilators_section', ['type' => 'section', 'title' => __('Invigilators'), 'visible' => false]);
        $this->field('special_needs_section', ['type' => 'section', 'title' => __('Special Need Accommodations'), 'visible' => false]);
        if ($this->action == 'edit' || $this->action == 'add') {
            $this->field('academic_period_id', ['entity' => $entity]);
            $this->field('examination_id', ['entity' => $entity]);
            $this->field('special_need_type_id', ['type' => 'custom_exam_centre_special_needs']);

            $this->field('subjects', ['type' => 'chosenSelect', 'entity' => $entity]);
            $this->field('create_as', ['type' => 'select', 'options' => $this->getSelectOptions($this->aliasField('create_as')), 'entity' => $entity]);
            $this->fields['institution_id']['visible'] = true;
            $this->fields['institution_id']['type'] = 'hidden';
            // to add logic for edit

            if ($this->action == 'add') {
                if ($entity->create_as == 'new') {
                    $this->fields['area_id'] = array_merge($this->fields['area_id'], ['visible' => true, 'type' => 'areapicker', 'source_model' => 'Area.Areas', 'displayCountry' => true]);
                    $this->fields['name']['visible'] = true;
                    $this->fields['code']['visible'] = true;
                    $this->fields['address']['visible'] = true;
                    $this->fields['postal_code']['visible'] = true;
                    $this->fields['contact_person']['visible'] = true;
                    $this->fields['telephone']['visible'] = true;
                    $this->fields['fax']['visible'] = true;
                    $this->fields['email']['visible'] = true;
                    $this->fields['website']['visible'] = true;
                } else if ($entity->create_as == 'existing') {
                    $this->field('institution_type');
                    $this->field('add_all_institutions');
                    $this->field('institutions');
                    $this->fields['name']['visible'] = false;
                } else {
                    $this->fields['name']['visible'] = false;
                }
                $this->fields['subjects_section']['visible'] = true;
                $this->fields['special_needs_section']['visible'] = true;

                // field order
                $this->setFieldOrder(['exam_centre_info_section', 'create_as', 'academic_period_id', 'examination_id', 'institution_type', 'add_all_institutions', 'institutions', 'code', 'name', 'area_id', 'address', 'postal_code', 'contact_person', 'telephone', 'fax', 'email', 'website', 'special_needs_section', 'special_need_type_id', 'subjects_section', 'subjects']);
            } else if ($this->action == 'edit') {
                $this->field('area_id', ['entity' => $entity, 'visible' => true, 'type' => 'areapicker', 'source_model' => 'Area.Areas', 'displayCountry' => true]);
                $this->fields['name']['visible'] = true;
                $this->fields['code']['visible'] = true;
                $this->fields['address']['visible'] = true;
                $this->fields['postal_code']['visible'] = true;
                $this->fields['contact_person']['visible'] = true;
                $this->fields['telephone']['visible'] = true;
                $this->fields['fax']['visible'] = true;
                $this->fields['email']['visible'] = true;
                $this->fields['website']['visible'] = true;

                if ($entity->institution_id != 0) {
                    $this->fields['name']['type'] = 'readonly';
                    $this->fields['code']['type'] = 'readonly';
                    $this->fields['address']['type'] = 'text';
                    $this->fields['address']['attr']['disabled'] = 'disabled';
                    $this->fields['postal_code']['type'] = 'readonly';
                    $this->fields['contact_person']['type'] = 'readonly';
                    $this->fields['telephone']['type'] = 'readonly';
                    $this->fields['fax']['type'] = 'readonly';
                    $this->fields['email']['type'] = 'readonly';
                    $this->fields['website']['type'] = 'readonly';
                }

                $this->field('linked_institutions', ['type' => 'chosenSelect', 'examination_id' => $entity->examination_id, 'education_grade_id' => $entity->examination->education_grade_id]);
                $this->fields['subjects_section']['visible'] = true;
                $this->fields['special_needs_section']['visible'] = true;
                $this->fields['linked_institutions_section']['visible'] = true;
                $this->fields['invigilators_section']['visible'] = true;
                $this->fields['exam_centre_info_section']['visible'] = true;
                $this->field('invigilators', ['type' => 'custom_invigilators']);

                // field order
                $this->setFieldOrder(['exam_centre_info_section', 'create_as', 'academic_period_id', 'examination_id', 'code', 'name', 'area_id', 'address', 'postal_code', 'contact_person', 'telephone', 'fax', 'email', 'website',  'special_needs_section', 'special_need_type_id', 'linked_institutions_section', 'linked_institutions', 'invigilators_section', 'invigilators', 'subjects_section', 'subjects']);
            }
        } else if ($this->action == 'view') {
            $this->fields['area_id'] = array_merge($this->fields['area_id'], ['visible' => true, 'type' => 'areapicker', 'source_model' => 'Area.Areas', 'displayCountry' => true]);
            $this->field('special_need_type_id', ['type' => 'custom_exam_centre_special_needs']);
            $this->field('subjects', [
                'type' => 'element',
                'element' => 'Examination.exam_centre_subjects'
            ]);
            $this->fields['code']['visible'] = true;
            $this->fields['address']['visible'] = true;
            $this->fields['postal_code']['visible'] = true;
            $this->fields['contact_person']['visible'] = true;
            $this->fields['telephone']['visible'] = true;
            $this->fields['fax']['visible'] = true;
            $this->fields['email']['visible'] = true;
            $this->fields['website']['visible'] = true;
            $this->fields['total_registered']['visible'] = true;
            $this->field('invigilators', ['type' => 'custom_invigilators']);
            $this->field('linked_institutions', [
                'type' => 'element',
                'element' => 'Examination.exam_centre_institutions'
            ]);
            // $this->fields['subjects_section']['visible'] = true;
            // $this->fields['linked_institutions_section']['visible'] = true;
            // $this->fields['invigilators_section']['visible'] = true;
            // $this->fields['exam_centre_info_section']['visible'] = true;

            $this->setFieldOrder(['exam_centre_info_section', 'code', 'name', 'academic_period_id', 'examination_id', 'total_registered', 'area_id', 'address', 'postal_code', 'contact_person', 'telephone', 'fax', 'email', 'website',  'special_needs_section', 'special_need_type_id', 'linked_institutions_section', 'linked_institutions', 'invigilators_section', 'invigilators', 'subjects_section', 'subjects']);
        }
    }

    public function onUpdateFieldTotalCapacity(Event $event, array $attr, $action, Request $request)
    {
        $attr['length'] = 5;
        return $attr;
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $attr['options'] = $this->AcademicPeriods->getYearList(['isEditable' => true]);
            $attr['onChangeReload'] = true;
        } else if ($action == 'edit') {
            if (isset($attr['entity'])) {
                $attr['attr']['value'] = $attr['entity']->_matchingData['AcademicPeriods']->name;
            }
            $attr['type'] = 'readonly';
        }
        return $attr;
    }

    public function onUpdateFieldExaminationId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $attr['options'] = [];
            if (isset($request->data[$this->alias()]['academic_period_id'])) {
                $academicPeriodId = $request->data[$this->alias()]['academic_period_id'];
                $attr['options'] = $this->Examinations->find('list')->where([$this->Examinations->aliasField('academic_period_id') => $academicPeriodId])->toArray();
                $attr['onChangeReload'] = true;
            }
        } else if ($action == 'edit') {
            if (isset($attr['entity'])) {
                $attr['attr']['value'] = $attr['entity']->_matchingData['Examinations']->name;
            }
            $attr['type'] = 'readonly';
        }
        return $attr;
    }

    public function onUpdateFieldSubjects(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $examinationId = 0;
            if (isset($request->data[$this->alias()]['examination_id'])) {
                $examinationId = $request->data[$this->alias()]['examination_id'];
            } else if ($attr['entity']->has('examination_id')) {
                $examinationId = $attr['entity']->examination_id;
            }
            $ExaminationItemsTable = $this->Examinations->ExaminationItems;

            $options = $ExaminationItemsTable
                ->find()
                ->contain('EducationSubjects')
                ->select([
                    'item_code' => $ExaminationItemsTable->aliasField('code'),
                    'item_name' => $ExaminationItemsTable->aliasField('name'),
                    'education_subject' => 'EducationSubjects.name',
                ])
                ->where([
                    $ExaminationItemsTable->aliasField('examination_id') => $examinationId
                ])
                ->hydrate(false)
                ->toArray();

            $attr['type'] = 'element';
            $attr['element'] = 'Examination.exam_centre_subjects';
            $attr['data'] = $options;
        } else if ($action == 'edit') {
            $entity = $attr['entity'];
            $subjects = [];
            foreach ($entity->examination_items as $item) {
                $educationSubject = '';
                if ($item->has('education_subject') && $item->education_subject->has('name')) {
                    $educationSubject = $item->education_subject->name;
                }

                $subjects[] = [
                    'item_code' => $item->code,
                    'item_name' => $item->name,
                    'education_subject' => $educationSubject
                ];
            }
            $attr['type'] = 'element';
            $attr['element'] = 'Examination.exam_centre_subjects';
            $attr['data'] = $subjects;
        }
        return $attr;
    }

    public function onGetCustomInvigilatorsElement(Event $event, $action, $entity, $attr, $options=[])
    {
        $tableHeaders = [__('OpenEMIS ID'), __('Invigilator')];
        $tableCells = [];
        $alias = $this->alias();
        $fieldKey = 'invigilators';
        $examinationId = $entity->examination_id;

        if ($action == 'view') {
            $associated = $entity->extractOriginal([$fieldKey]);
            if (!empty($associated[$fieldKey])) {
                foreach ($associated[$fieldKey] as $key => $obj) {
                    $rowData = [];
                    $rowData[] = $obj->openemis_no;
                    $rowData[] = $obj->name;

                    $tableCells[] = $rowData;
                }
            }
        } else if ($action == 'edit') {
            $examCentreId = $this->paramsDecode($this->paramsPass(0))['id'];

            if (!$entity->isNew()) {
                $tableHeaders[] = ''; // for delete column
                $Form = $event->subject()->Form;
                $Form->unlockField('ExaminationCentres.invigilators');

                if ($this->request->is(['get'])) {
                    if (!array_key_exists($alias, $this->request->data)) {
                        $this->request->data[$alias] = [$fieldKey => []];
                    } else {
                        $this->request->data[$alias][$fieldKey] = [];
                    }

                    $associated = $entity->extractOriginal([$fieldKey]);
                    if (!empty($associated[$fieldKey])) {
                        foreach ($associated[$fieldKey] as $key => $obj) {
                            $this->request->data[$alias][$fieldKey][$key] = [
                                'id' => $obj->id,
                                'openemis_no' => $obj->openemis_no,
                                'name' => $obj->name,
                                '_joinData' => ['academic_period_id' => $entity->academic_period_id, 'examination_id' => $entity->examination_id]
                            ];
                        }
                    }
                }

                // refer to editOnAddInvigilator for http post
                if ($this->request->data("$alias.$fieldKey")) {
                    $associated = $this->request->data("$alias.$fieldKey");

                    foreach ($associated as $key => $obj) {
                        $invigilatorId = $obj['id'];
                        $openemisId = $obj['openemis_no'];
                        $name = $obj['name'];
                        $joinData = $obj['_joinData'];

                        $rowData = [];

                        $cell = $name;
                        $cell .= $Form->hidden("$alias.$fieldKey.$key.id", ['value' => $invigilatorId, 'autocomplete-exclude' => $invigilatorId]);
                        $cell .= $Form->hidden("$alias.$fieldKey.$key.openemis_no", ['value' => $openemisId]);
                        $cell .= $Form->hidden("$alias.$fieldKey.$key.name", ['value' => $name]);
                        $cell .= $Form->hidden("$alias.$fieldKey.$key._joinData.academic_period_id", ['value' => $joinData['academic_period_id']]);
                        $cell .= $Form->hidden("$alias.$fieldKey.$key._joinData.examination_id", ['value' => $joinData['examination_id']]);
                        $cell .= $Form->hidden("$alias.$fieldKey.$key._joinData.examination_centre_id", ['value' => $examCentreId]);

                        $rowData[] = $openemisId;
                        $rowData[] = $cell;
                        $rowData[] = $this->getDeleteButton();
                        $tableCells[] = $rowData;
                    }
                }
            }
        }
        $attr['examination_id'] = $examinationId;
        $attr['tableHeaders'] = $tableHeaders;
        $attr['tableCells'] = $tableCells;

        return $event->subject()->renderElement('Examination.ExaminationCentres/' . $fieldKey, ['attr' => $attr]);
    }

    public function addEditOnSelectSpecialNeedType(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $ADD_ALL = -1;
        $fieldKey = 'examination_centre_special_needs';

        $SpecialNeedTypesTable = $this->ExaminationCentreSpecialNeeds->SpecialNeedTypes;
        $allSpecialNeeds = $SpecialNeedTypesTable->getVisibleNeedTypes();
        $id = $data[$this->alias()]['special_need_type_id'];

        if (empty($data[$this->alias()][$fieldKey])) {
            $data[$this->alias()][$fieldKey] = [];
        }

        if ($id == $ADD_ALL) {
            $selectedNeeds = array_column($data[$this->alias()][$fieldKey], 'special_need_type_id');
            foreach ($allSpecialNeeds as $key => $obj) {
                if (!empty($selectedNeeds) && in_array($key, $selectedNeeds)) {
                    continue;
                }

                $data[$this->alias()][$fieldKey][] = [
                    'special_need_type_id' => $key,
                    'name' => $obj
                ];
            }
            $data[$this->alias()]['special_need_type_id'] = '';

        } else if ($id > 0) {
            try {
                $obj = $SpecialNeedTypesTable->get($id);

                $data[$this->alias()][$fieldKey][] = [
                    'special_need_type_id' => $id,
                    'name' => $obj->name
                ];

                $data[$this->alias()]['special_need_type_id'] = '';

            } catch (RecordNotFoundException $ex) {
                Log::write('debug', __METHOD__ . ': Record not found for special need type id: ' . $id);
            }
        }
    }

    public function onGetCustomExamCentreSpecialNeedsElement(Event $event, $action, $entity, $attr, $options=[])
    {
        $requestData = $this->request->data;
        $tableHeaders = [__('Special Need Type')];
        $tableCells = [];
        $alias = $this->alias();
        $fieldKey = 'examination_centre_special_needs';

        if ($action == 'view') {
            $associated = $entity->extractOriginal([$fieldKey]);
            if (!empty($associated[$fieldKey])) {
                foreach ($associated[$fieldKey] as $key => $obj) {
                    $rowData = [];
                    $rowData[] = $obj->special_need_type->name;
                    $tableCells[] = $rowData;
                }
            }

        } else if ($action == 'edit') {
            // options for special needs types
            $SpecialNeedTypesTable = $this->ExaminationCentreSpecialNeeds->SpecialNeedTypes;
            $specialNeedsOptions = $SpecialNeedTypesTable->getVisibleNeedTypes();

            $tableHeaders[] = ''; // for delete column
            $Form = $event->subject()->Form;
            $Form->unlockField('ExaminationCentres.examination_centre_special_needs');

            $selectedSpecialNeeds = [];
            if ($this->request->is(['get'])) {
                $associated = $entity->extractOriginal([$fieldKey]);
                if (!empty($associated[$fieldKey])) {
                    foreach ($associated[$fieldKey] as $key => $obj) {
                        $selectedSpecialNeeds[] = [
                            'special_need_type_id' => $obj->special_need_type_id,
                            'name' => $obj->special_need_type->name
                        ];
                    }
                }

            } else if ($this->request->is(['post', 'put'])) {
                if (array_key_exists($fieldKey, $requestData[$alias])) {
                    foreach ($requestData[$alias][$fieldKey] as $key => $obj) {
                        $selectedSpecialNeeds[] = $obj;
                    }
                }
            }

            if (!empty($selectedSpecialNeeds)) {
                foreach ($selectedSpecialNeeds as $key => $obj) {
                    $examinationId = $entity->examination_id;
                    $academicPeriodId = $entity->academic_period_id;
                    $specialNeedTypeId = $obj['special_need_type_id'];
                    $name = $obj['name'];

                    $cell = $name;
                    $cell .= $Form->hidden("$alias.$fieldKey.$key.examination_id", ['value' => $examinationId]);
                    $cell .= $Form->hidden("$alias.$fieldKey.$key.academic_period_id", ['value' => $academicPeriodId]);
                    $cell .= $Form->hidden("$alias.$fieldKey.$key.special_need_type_id", ['value' => $specialNeedTypeId]);
                    $cell .= $Form->hidden("$alias.$fieldKey.$key.name", ['value' => $name]);

                    // reload special needs options when an item is deleted
                    $attrDelete = ['onclick' => "jsTable.doRemove(this);$('#reload').val('delete').click();"];

                    $rowData = [];
                    $rowData[] = $cell;
                    $rowData[] = $this->getDeleteButton($attrDelete);
                    $tableCells[] = $rowData;

                    // remove selected option from dropdown list
                    unset($specialNeedsOptions[$obj['special_need_type_id']]);
                }
            }

            $attr['options'] = $specialNeedsOptions;
            $attr['addAll'] = true;
        }

        $attr['tableHeaders'] = $tableHeaders;
        $attr['tableCells'] = $tableCells;
        return $event->subject()->renderElement('../ControllerAction/table_with_dropdown', ['attr' => $attr]);
    }

    public function onGetName(Event $event, Entity $entity)
    {
        return $entity->code_name;
    }

    public function onUpdateFieldAreaId(Event $event, array $attr, $action, Request $request)
    {
        $entity = $attr['entity'];
        if ($entity->institution_id != 0) {
            $attr['type'] = 'readonly';
            $attr['attr']['value'] = __($entity->_matchingData['Areas']->name);
        }
        return $attr;
    }

    public function onUpdateFieldInstitutionType(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $attr['options'] = $this->Institutions->Types
                ->find('list')
                ->find('visible')
                ->toArray();

            $attr['type'] = 'select';
            $attr['onChangeReload'] = true;
        }
        return $attr;
    }

    public function onUpdateFieldLinkedInstitutions(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {
            $options = $this->Institutions->find()
                    ->innerJoinWith('InstitutionGrades', function ($q) use ($attr) {
                        return $q->where(['InstitutionGrades.education_grade_id' => $attr['education_grade_id']]);
                    })
                    ->leftJoin(['ExaminationCentresInstitutions' => 'examination_centres_institutions'], [
                        'ExaminationCentresInstitutions.institution_id = '.$this->Institutions->aliasField('id'),
                        'ExaminationCentresInstitutions.examination_id' => $attr['examination_id']
                    ])
                    ->where(['ExaminationCentresInstitutions.institution_id IS NULL', $this->Institutions->aliasField('classification') => 1])
                    ->group([$this->Institutions->aliasField('id')]);

            if ($action == 'edit') {
                $examCentreId = $this->paramsDecode($this->paramsPass(0))['id'];
                $options = $options->orWhere(['ExaminationCentresInstitutions.examination_centre_id' => $examCentreId]);
            }

            $institutions = [];
            foreach ($options as $value) {
                $institutions[$value->id] = $value->code.' - '.$value->name;
            }
            $attr['options'] = $institutions;

            return $attr;
        }
    }

    public function onUpdateFieldAddAllInstitutions(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $examinationId = isset($request->data[$this->alias()]['examination_id']) ? $request->data[$this->alias()]['examination_id'] : 0;
            $institutionOptions = $this->Institutions->find('NotExamCentres', ['examination_id' => $examinationId]);

            if (!empty($request->data[$this->alias()]['institution_type'])) {
                $type = $request->data[$this->alias()]['institution_type'];
                $institutionOptions->where([$this->Institutions->aliasField('institution_type_id') => $type]);
            }

            $institutionsCount = $institutionOptions->count();

            $selectOptions = [];
            if ($institutionsCount != 0) {
                $yesOption = __('Yes') . ' - ' . $institutionsCount . ' ' . __('institutions selected');
                $selectOptions = [0 => __('No'), 1 => $yesOption];
            }

            $attr['type'] = 'select';
            $attr['options'] = $selectOptions;
            $attr['select'] = false;
            $attr['default'] = 0; // default selected is no
            $attr['onChangeReload'] = 'changeAddAllInstitutions';
            return $attr;
        }
    }

    public function addOnChangeAddAllInstitutions(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        if (array_key_exists($this->alias(), $data)) {
            if (array_key_exists('institutions', $data[$this->alias()])) {
                $data[$this->alias()]['institutions'] = '';
            }
        }
    }

    public function onUpdateFieldInstitutions(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $addAllInstitutions = isset($request->data[$this->alias()]['add_all_institutions']) ? $request->data[$this->alias()]['add_all_institutions'] : 0;

            if ($addAllInstitutions == 1) {
                $attr['type'] = 'hidden';

            } else {
                $examinationId = isset($request->data[$this->alias()]['examination_id']) ? $request->data[$this->alias()]['examination_id'] : 0;
                $institutionOptions = $this->Institutions
                    ->find('list', [
                        'keyField' => 'id',
                        'valueField' => 'code_name'
                    ])
                    ->find('NotExamCentres', ['examination_id' => $examinationId]);

                // if no institution type is selected, all institutions will be shown
                if (!empty($request->data[$this->alias()]['institution_type'])) {
                    $type = $request->data[$this->alias()]['institution_type'];
                    $institutionOptions->where([$this->Institutions->aliasField('institution_type_id') => $type]);
                }

                $attr['type'] = 'chosenSelect';
                $attr['options'] = $institutionOptions->toArray();
                $attr['fieldName'] = $this->alias().'.institutions';
            }

            return $attr;
        }
    }

    public function onUpdateFieldCreateAs(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $attr['onChangeReload'] = true;
        } else if ($action == 'edit') {
            if ($attr['entity']->institution_id != 0) {
                $attr['attr']['value'] = $attr['options']['existing'];
            } else {
                $attr['attr']['value'] = $attr['options']['new'];
            }
            $attr['type'] = 'disabled';
        }
        return $attr;
    }

    public function addBeforePatch(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra)
    {
        $requestData[$this->alias()]['institution_id'] = 0;
        if (!isset($requestData[$this->alias()]['area_id'])) {
            $requestData[$this->alias()]['area_id'] = 0;
        }

        if ($requestData[$this->alias()]['create_as'] == 'new') {
            $patchOptions['validate'] = 'noInstitutions';
        } else if ($requestData[$this->alias()]['create_as'] == 'existing' && $requestData[$this->alias()]['add_all_institutions'] == 1) {
            $patchOptions['validate'] = 'allInstitutions';
        } else {
            $patchOptions['validate'] = 'institutions';
        }

        $academicPeriodId = $requestData[$this->alias()]['academic_period_id'];
        $examinationId = $requestData[$this->alias()]['examination_id'];

        // Subjects logic
        $ExaminationItemsTable = $this->Examinations->ExaminationItems;
        $examinationItems = $ExaminationItemsTable
                ->find()
                ->contain('EducationSubjects')
                ->select([$ExaminationItemsTable->aliasField('id'), $ExaminationItemsTable->aliasField('education_subject_id')])
                ->where([
                    $ExaminationItemsTable->aliasField('examination_id') => $examinationId
                ])
                ->toArray();

        $examinationCentreSubjects = [];
        if (is_array($examinationItems)) {
            foreach($examinationItems as $item) {
                $examinationCentreSubjects[] = [
                    'examination_id' => $examinationId,
                    'academic_period_id' => $academicPeriodId,
                    'education_subject_id' => $item->education_subject_id,
                    'examination_item_id' => $item->id,
                ];
            }
        }

        $requestData[$this->alias()]['examination_centre_subjects'] = $examinationCentreSubjects;
    }

    public function addBeforeSave(Event $event, $entity, $requestData, $extra)
    {
        $process = function ($model, $entity) use ($requestData) {

            if (isset($requestData[$model->alias()]['institutions']) && !empty($requestData[$model->alias()]['institutions'])) {
                $institutions = $requestData[$model->alias()]['institutions'];
                $newEntities = [];
                if (is_array($institutions)) {
                    foreach ($institutions as $institution) {
                        $institutionRecord = $model->Institutions->get($institution);
                        $requestData['institution_id'] = $institution;
                        $requestData['area_id'] = $institutionRecord->area_id;
                        $requestData['name'] = $institutionRecord->name;
                        $requestData['code'] = $institutionRecord->code;
                        $requestData['address'] = $institutionRecord->address;
                        $requestData['postal_code'] = $institutionRecord->postal_code;
                        $requestData['contact_person'] = $institutionRecord->contact_person;
                        $requestData['telephone'] = $institutionRecord->telephone;
                        $requestData['fax'] = $institutionRecord->fax;
                        $requestData['email'] = $institutionRecord->email;
                        $requestData['website'] = $institutionRecord->website;

                        $newEntities[] = $model->newEntity($requestData->getArrayCopy());
                    }
                }
                return $model->saveMany($newEntities);

            } else if (isset($requestData[$model->alias()]['add_all_institutions']) && $requestData[$model->alias()]['add_all_institutions'] == 1) {
                if (empty($entity->errors())) {
                    if (!empty($requestData[$this->alias()]['examination_id']) && !empty($requestData[$this->alias()]['academic_period_id'])) {
                        $examinationId = $requestData[$model->alias()]['examination_id'];
                        $academicPeriodId = $requestData[$model->alias()]['academic_period_id'];
                        $institutionTypeId = !empty($requestData[$model->alias()]['institution_type']) ? $requestData[$model->alias()]['institution_type'] : '';

                        $specialNeedIds = [];
                        if (isset($requestData[$model->alias()]['examination_centre_special_needs'])) {
                            $specialNeedIds = array_column($requestData[$model->alias()]['examination_centre_special_needs'], 'special_need_type_id');
                        }

                        $subjectIds = [];
                        if (isset($requestData[$model->alias()]['examination_centre_subjects'])) {
                            $subjectIds = array_column($requestData[$model->alias()]['examination_centre_subjects'], 'education_subject_id');
                        }

                        // put special needs and subjects into System Processes params
                        $SystemProcesses = TableRegistry::get('SystemProcesses');
                        $name = 'AddAllInstitutionsExamCentre';
                        $pid = '';
                        $processModel = $model->registryAlias();
                        $eventName = '';
                        $passArray = ['examination_id' => $examinationId, 'special_needs' => $specialNeedIds, 'subjects' => $subjectIds];
                        $params = json_encode($passArray);
                        $systemProcessId = $SystemProcesses->addProcess($name, $pid, $processModel, $eventName, $params);

                        $this->triggerAddAllInstitutionsExamCentreShell($examinationId, $academicPeriodId, $systemProcessId, $institutionTypeId);
                        $this->Alert->warning($this->aliasField('savingProcessStarted'), ['reset' => true]);
                        return true;
                    }
                }
            } else {
                return $model->save($entity);
            }
        };

        return $process;
    }

    private function triggerAddAllInstitutionsExamCentreShell($examinationId, $academicPeriodId, $systemProcessId, $institutionTypeId = null)
    {
        $args = '';
        $args .= !is_null($examinationId) ? ' '.$examinationId : '';
        $args .= !is_null($academicPeriodId) ? ' '.$academicPeriodId : '';
        $args .= !is_null($systemProcessId) ? ' '.$systemProcessId : '';
        $args .= !is_null($institutionTypeId) ? ' '.$institutionTypeId : '';

        $cmd = ROOT . DS . 'bin' . DS . 'cake AddAllInstitutionsExamCentre '.$args;
        $logs = ROOT . DS . 'logs' . DS . 'AddAllInstitutionsExamCentre.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;

        try {
            $pid = exec($shellCmd);
            Log::write('debug', $shellCmd);
        } catch(\Exception $ex) {
            Log::write('error', __METHOD__ . ' exception when add all institutions : '. $ex);
        }
    }

    public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        $extra['excludedModels'] = [
            $this->ExaminationCentreSubjects->alias(), $this->ExaminationCentreSpecialNeeds->alias()
        ];
    }
}
