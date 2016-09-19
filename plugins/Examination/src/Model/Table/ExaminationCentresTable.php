<?php
namespace Examination\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Traits\OptionsTrait;
use ArrayObject;
use Cake\Validation\Validator;

class ExaminationCentresTable extends ControllerActionTable {
    use OptionsTrait;

    public function initialize(array $config)
    {
        $this->table('examination_centres');
        parent::initialize($config);
        $this->belongsTo('Examinations', ['className' => 'Examination.Examinations']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('Areas', ['className' => 'Area.Areas']);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        $validator
            ->requirePresence('create_as', [
                'provider' => 'table',
                'on' => 'create'
            ]);
        return $validator;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('academic_period_id', ['type' => 'select']);
        $this->field('examination_id', ['type' => 'select']);
        $this->field('special_need_types');
        $this->field('institution_id', ['visible' => false]);
        $this->field('name', ['visible' => false]);
        $this->field('area_id', ['visible' => false]);
        $this->field('code', ['visible' => false]);
        $this->field('address', ['visible' => false]);
        $this->field('postal_code', ['visible' => false]);
        $this->field('contact_person', ['visible' => false]);
        $this->field('telephone', ['visible' => false]);
        $this->field('fax', ['visible' => false]);
        $this->field('email', ['visible' => false]);
        $this->field('website', ['visible' => false]);

    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        $this->controller->getExamsTab();
        if ($this->action == 'edit' || $this->action == 'add') {
            $entity = $extra['entity'];
            $this->field('create_as', ['type' => 'select', 'options' => $this->getSelectOptions($this->aliasField('create_as')), 'entity' => $entity]);
            // to add logic for edit
            if ($entity->create_as == 'new') {
                $this->field('name', ['visible' => true]);
                $this->field('area_id', ['visible' => true]);
                $this->field('code', ['visible' => true]);
                $this->field('address', ['visible' => true]);
                $this->field('postal_code', ['visible' => true]);
                $this->field('contact_person', ['visible' => true]);
                $this->field('telephone', ['visible' => true]);
                $this->field('fax', ['visible' => true]);
                $this->field('email', ['visible' => true]);
                $this->field('website', ['visible' => true]);
            } else if ($entity->create_as == 'existing') {
                $this->field('institutions', ['visible' => true]);
            }
        }
    }

    public function addEditBeforeAction(Event $event) {
        $this->field('subjects', ['type' => 'chosenSelect']);
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $attr['options'] = $this->AcademicPeriods->getYearList(['isEditable' => true]);
            $attr['onChangeReload'] = true;
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
            $attr['type'] = 'readonly';
        }
        return $attr;
    }

    public function onUpdateFieldSubjects(Event $event, array $attr, $action, Request $request)
    {
        if (isset($request->data[$this->alias()]['examination_id'])) {
            $examinationId = $request->data[$this->alias()]['examination_id'];
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
        }
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
            $attr['type'] = 'readonly';
        }
        return $attr;
    }

    public function addBeforePatch(Event $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra)
    {
        $entity->institution_id = 0;
        $entity->area_id = 0;
    }

    public function addBeforeSave(Event $event, $entity, $requestData, $extra)
    {

    }
}
