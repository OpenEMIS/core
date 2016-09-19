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
        $this->table('examination_centres');
        parent::initialize($config);
        $this->addBehavior('Area.Areapicker');
        $this->belongsTo('Examinations', ['className' => 'Examination.Examinations']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('Areas', ['className' => 'Area.Areas']);
        $this->hasMany('ExaminationCentreSubjects', ['className' => 'Examination.ExaminationCentreSubjects']);
        $this->hasMany('ExaminationCentreSpecialNeeds', ['className' => 'Examination.ExaminationCentreSpecialNeeds']);
    }

    public function implementedEvents()
    {
        $event = parent::implementedEvents();
        $event['ControllerAction.Model.viewEdit.afterQuery'] = 'viewEditAfterQuery';
        return $event;
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        $validator
            ->requirePresence('create_as', 'create');
        return $validator;
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
        $query->contain(['ExaminationCentreSubjects'])
            ->matching('Examinations')
            ->matching('Areas')
            ->matching('AcademicPeriods');
    }


    public function viewEditAfterQuery(Event $event, $entity, $extra)
    {
        $subjects = [];
        foreach ($entity->examination_centre_subjects as $subject) {
            $subjects[] = $subject->education_subject_id;
        }
        $this->request->data[$this->alias()]['subjects']['_ids'] = $subjects;
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        $this->controller->getExamsTab();
        $entity = $extra['entity'];
        if ($this->action == 'edit' || $this->action == 'add') {
            $this->field('academic_period_id', ['entity' => $entity]);
            $this->field('examination_id', ['entity' => $entity]);
            $this->field('special_need_types', ['type' => 'chosenSelect', 'entity' => $entity, 'after' => 'examination_id']);
            $this->field('subjects', ['type' => 'chosenSelect', 'entity' => $entity, 'after' => 'special_need_types']);
            $this->field('create_as', ['type' => 'select', 'options' => $this->getSelectOptions($this->aliasField('create_as')), 'entity' => $entity]);
            $this->field('name', ['visible' => false]);

            // to add logic for edit
        }
        if ($this->action == 'add') {
            if ($entity->create_as == 'new') {
                $this->field('area_id', ['visible' => true, 'type' => 'areapicker', 'source_model' => 'Area.Areas', 'displayCountry' => true]);
                $this->fields['code']['visible'] = true;
                $this->fields['address']['visible'] = true;
                $this->fields['postal_code']['visible'] = true;
                $this->fields['contact_person']['visible'] = true;
                $this->fields['telephone']['visible'] = true;
                $this->fields['fax']['visible'] = true;
                $this->fields['email']['visible'] = true;
                $this->fields['website']['visible'] = true;
            } else if ($entity->create_as == 'existing') {
                $this->field('institutions');
                $this->fields['name']['visible'] = false;

            }
        } else if ($this->action == 'edit') {
            $this->field('area_id', ['entity' => $entity, 'visible' => true, 'type' => 'readonly']);
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
                $this->fields['address']['type'] = 'readonly';
                $this->fields['postal_code']['type'] = 'readonly';
                $this->fields['contact_person']['type'] = 'readonly';
                $this->fields['telephone']['type'] = 'readonly';
                $this->fields['fax']['type'] = 'readonly';
                $this->fields['email']['type'] = 'readonly';
                $this->fields['website']['type'] = 'readonly';
            }
        }
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

    public function onUpdateFieldAreaId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {
            if (isset($attr['entity'])) {
                $attr['attr']['value'] = $attr['entity']->_matchingData['Areas']->name;
            }
            $attr['type'] = 'readonly';
        }
        return $attr;
    }

    public function onUpdateFieldSpecialNeedTypes(Event $event, array $attr, $action, Request $request)
    {
        $SpecialNeedTypesTable = $this->ExaminationCentreSpecialNeeds->SpecialNeedTypes;
        $attr['options'] = $SpecialNeedTypesTable->find('list')->toArray();
        return $attr;
    }

    public function onUpdateFieldSubjects(Event $event, array $attr, $action, Request $request)
    {
        $examinationId = 0;
        if (isset($request->data[$this->alias()]['examination_id'])) {
            $examinationId = $request->data[$this->alias()]['examination_id'];
        } else if ($attr['entity']->has('examination_id')) {
            $examinationId = $attr['entity']->examination_id;
        }
        $ExaminationItemsTable = $this->Examinations->ExaminationItems;
            $attr['options'] = $ExaminationItemsTable
                ->find('list', [
                    'keyField' => 'subject_id',
                    'valueField' => 'subject_name'
                ])
                ->matching('EducationSubjects')
                ->select([
                    'subject_name' => 'EducationSubjects.name',
                    'subject_id' => $ExaminationItemsTable->aliasField('education_subject_id')
                ])
                ->where([
                    $ExaminationItemsTable->aliasField('examination_id') => $examinationId
                ])
                ->toArray();
        $attr['empty'] = false;
        return $attr;
    }

    public function onUpdateFieldInstitutions(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $attr['type'] = 'chosenSelect';
            $attr['options'] = $this->Institutions->find('list')->toArray();
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
            $attr['type'] = 'readonly';
        }
        return $attr;
    }

    public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra)
    {
        $requestData[$this->alias()]['institution_id'] = 0;
        if (!isset($requestData[$this->alias()]['area_id'])) {
            $requestData[$this->alias()]['area_id'] = 0;
        }

        $academicPeriodId = $requestData[$this->alias()]['academic_period_id'];

        // Subjects logic
        $subjects = $requestData[$this->alias()]['subjects']['_ids'];
        $examinationCentreSubjects = [];
        foreach($subjects as $subject) {
            $examinationCentreSubjects[] = [
                'id' => Text::uuid(),
                'academic_period_id' => $academicPeriodId,
                'education_subject_id' => $subject,
            ];
        }
        $requestData[$this->alias()]['examination_centre_subjects'] = $examinationCentreSubjects;
        unset($requestData[$this->alias()]['subjects']);

        // Special needs logic

    }

    public function addBeforeSave(Event $event, $entity, $requestData, $extra)
    {
        $process = function ($model, $entity) use ($requestData) {
            if ($entity->has('institutions')) {
                $institutions = $entity->institutions['_ids'];
                $newEntities = [];
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
                return $model->saveMany($newEntities);
            } else {
                return $model->save($entity);
            }
        };

        return $process;
    }
}
