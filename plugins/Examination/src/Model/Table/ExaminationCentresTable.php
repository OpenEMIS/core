<?php
namespace Examination\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Traits\OptionsTrait;
use ArrayObject;
use Cake\Validation\Validator;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Utility\Text;

class ExaminationCentresTable extends ControllerActionTable {
    use OptionsTrait;

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->addBehavior('Area.Areapicker');
        $this->belongsTo('Examinations', ['className' => 'Examination.Examinations']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('Areas', ['className' => 'Area.Areas']);
        $this->hasMany('ExaminationCentreSubjects', ['className' => 'Examination.ExaminationCentreSubjects', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ExaminationCentreSpecialNeeds', ['className' => 'Examination.ExaminationCentreSpecialNeeds', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ExaminationCentreStudents', ['className' => 'Examination.ExaminationCentreStudents', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->setDeleteStrategy('restrict');
    }

    public function implementedEvents() {
        $events = parent::implementedEvents();
        $newEvent = [
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
            ->requirePresence('code')
            ->requirePresence('name')
            ->add('total_capacity', 'ruleValidateNumeric', [
                'rule' => ['numericPositive']
            ])
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

    private function getExaminationOptions($selectedAcademicPeriod) {
        $examinationOptions = $this->Examinations
            ->find('list')
            ->where([$this->Examinations->aliasField('academic_period_id') => $selectedAcademicPeriod])
            ->toArray();

        return $examinationOptions;
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
        $query->contain(['ExaminationCentreSubjects.EducationSubjects'])
            ->contain('ExaminationCentreSpecialNeeds.SpecialNeedTypes')
            ->matching('Examinations')
            ->matching('Areas')
            ->matching('AcademicPeriods');
    }


    public function editOnInitialize(Event $event, Entity $entity)
    {
        $subjects = [];
        foreach ($entity->examination_centre_subjects as $subject) {
            $subjects[] = $subject->education_subject_id;
        }
        $this->request->data[$this->alias()]['subjects']['_ids'] = $subjects;

        $specialNeeds = [];
        foreach ($entity->examination_centre_special_needs as $specialNeed) {
            $specialNeeds[] = $specialNeed->special_need_type_id;
        }
        $this->request->data[$this->alias()]['special_need_types']['_ids'] = $specialNeeds;
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        $this->controller->getExamsTab();
        $this->fields['total_registered']['visible'] = false;
        $entity = $extra['entity'];
        if ($this->action == 'edit' || $this->action == 'add') {
            $this->field('academic_period_id', ['entity' => $entity]);
            $this->field('examination_id', ['entity' => $entity]);
            $this->field('special_need_types', ['type' => 'chosenSelect', 'entity' => $entity]);
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
                    $this->field('institution_type', ['before' => 'institutions']);
                    $this->field('institutions');
                    $this->fields['name']['visible'] = false;
                } else {
                    $this->fields['name']['visible'] = false;
                }
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
            }
            $this->field('total_capacity', ['type' => 'string']);

            // field order
            $this->setFieldOrder(['create_as', 'academic_period_id', 'examination_id', 'special_need_types', 'subjects', 'total_capacity', 'code', 'name', 'area_id', 'address', 'postal_code', 'contact_person', 'telephone', 'fax', 'email', 'website']);
        } else if ($this->action == 'view') {
            $this->fields['area_id'] = array_merge($this->fields['area_id'], ['visible' => true, 'type' => 'areapicker', 'source_model' => 'Area.Areas', 'displayCountry' => true]);
            $this->field('special_need_types');
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

            $this->setFieldOrder(['code', 'name', 'academic_period_id', 'examination_id', 'special_need_types', 'subjects', 'total_registered', 'total_capacity', 'area_id', 'address', 'postal_code', 'contact_person', 'telephone', 'fax', 'email', 'website']);
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

    public function onUpdateFieldSpecialNeedTypes(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $SpecialNeedTypesTable = $this->ExaminationCentreSpecialNeeds->SpecialNeedTypes;
            $attr['options'] = $SpecialNeedTypesTable
                ->find('list')
                ->toArray();
            $attr['empty'] = false;
            $attr['fieldName'] = $this->alias().'.special_need_types';
        } else if ($action == 'edit') {
            $entity = $attr['entity'];
            $specialNeeds = [];
            foreach ($entity->examination_centre_special_needs as $specialNeed) {
                $specialNeeds[] = __($specialNeed->special_need_type->name);
            }
            $attr['attr']['value'] = implode(', ', $specialNeeds);
            $attr['attr']['disabled'] = 'disabled';
            $attr['type'] = 'text';
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
                ->matching('EducationSubjects')
                ->select(['subject_code' => 'EducationSubjects.code', 'subject_name' => 'EducationSubjects.name'])
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
            foreach ($entity->examination_centre_subjects as $subject) {
                $subjects[] = [
                    'subject_code' => $subject->education_subject->code,
                    'subject_name' => $subject->education_subject->name
                ];
            }
            $attr['type'] = 'element';
            $attr['element'] = 'Examination.exam_centre_subjects';
            $attr['data'] = $subjects;
        }
        return $attr;
    }

    public function onGetName(Event $event, Entity $entity)
    {
        return $entity->code_name;
    }

    public function onGetSpecialNeedTypes(Event $event, Entity $entity)
    {
        $specialNeeds = [];
        foreach ($entity->examination_centre_special_needs as $specialNeed) {
            $specialNeeds[] = __($specialNeed->special_need_type->name);
        }
        return implode('<br/>', $specialNeeds);
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
                ->toArray();

            $attr['type'] = 'select';
            $attr['onChangeReload'] = true;
        }
        return $attr;
    }

    public function onUpdateFieldInstitutions(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $attr['type'] = 'chosenSelect';
            $examinationId = isset($request->data[$this->alias()]['examination_id']) ? $request->data[$this->alias()]['examination_id'] : 0;
<<<<<<< HEAD
            $attr['options'] = $this->Institutions
                ->find('list', [
                    'keyField' => 'id',
                    'valueField' => 'code_name'
                ])
                ->find('NotExamCentres', ['examination_id' => $examinationId])
                ->toArray();
=======
            $institutionOptions = $this->Institutions
                ->find('list')
                ->find('NotExamCentres', ['examination_id' => $examinationId]);

            // if no institution type is selected, all institutions will be shown
            if (!empty($request->data[$this->alias()]['institution_type'])) {
                $type = $request->data[$this->alias()]['institution_type'];
                $institutionOptions->where([$this->Institutions->aliasField('institution_type_id') => $type]);
            }

            $attr['options'] = $institutionOptions->toArray();
>>>>>>> b163b9b3c97eda84669a23180af13f30cc6a95ef
            $attr['fieldName'] = $this->alias().'.institutions';
        }
        return $attr;
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
        } else {
            $patchOptions['validate'] = 'institutions';
        }

        $academicPeriodId = $requestData[$this->alias()]['academic_period_id'];
        $examinationId = $requestData[$this->alias()]['examination_id'];

        // Subjects logic
        // $subjects = $requestData[$this->alias()]['subjects'];
        $ExaminationItemsTable = $this->Examinations->ExaminationItems;
        $subjects = $ExaminationItemsTable
                ->find('list', [
                    'keyField' => 'subject_id',
                    'valueField' => 'subject_id'
                ])
                ->matching('EducationSubjects')
                ->select([
                    'subject_id' => $ExaminationItemsTable->aliasField('education_subject_id')
                ])
                ->where([
                    $ExaminationItemsTable->aliasField('examination_id') => $examinationId
                ])
                ->toArray();

        $examinationCentreSubjects = [];
        if (is_array($subjects)) {
            foreach($subjects as $subject) {
                $examinationCentreSubjects[] = [
                    'id' => Text::uuid(),
                    'examination_id' => $examinationId,
                    'academic_period_id' => $academicPeriodId,
                    'education_subject_id' => $subject,
                ];
            }
        }

        $requestData[$this->alias()]['examination_centre_subjects'] = $examinationCentreSubjects;

        // Special needs logic
        $specialNeedTypes = $requestData[$this->alias()]['special_need_types'];
        $examinationCentreSpecialNeeds = [];
        if (is_array($specialNeedTypes)) {
            foreach($specialNeedTypes as $specialNeed) {
                $examinationCentreSpecialNeeds[] = [
                    'id' => Text::uuid(),
                    'examination_id' => $examinationId,
                    'academic_period_id' => $academicPeriodId,
                    'special_need_type_id' => $specialNeed,
                ];
            }
        }

        $requestData[$this->alias()]['examination_centre_special_needs'] = $examinationCentreSpecialNeeds;

    }

    public function addBeforeSave(Event $event, $entity, $requestData, $extra)
    {
        $process = function ($model, $entity) use ($requestData) {
            if ($entity->has('institutions')) {
                $institutions = $entity->institutions;
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
            } else {
                return $model->save($entity);
            }
        };

        return $process;
    }

    public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        $extra['excludedModels'] = [
            $this->ExaminationCentreSubjects->alias(), $this->ExaminationCentreSpecialNeeds->alias()
        ];
    }
}
