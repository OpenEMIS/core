<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\Chronos\Date;
use Cake\Chronos\Chronos;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Network\Session;
use Cake\ORM\TableRegistry;

use App\Model\Table\ControllerActionTable;

class AppraisalsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('staff_appraisals');
        parent::initialize($config);

        // setting this up to be overridden in viewAfterAction(), this code is required for file download
        $this->behaviors()->get('ControllerAction')->config(
            'actions.download.show',
            true
        );

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('StaffAppraisalTypes', ['className' => 'Staff.StaffAppraisalTypes']);
        $this->belongsTo('CompetencySets', ['className' => 'Staff.CompetencySets']);

        $this->belongsToMany('Competencies', [
            'className' => 'Staff.Competencies',
            'joinTable' => 'staff_appraisal_competencies',
            'foreignKey' => 'staff_appraisal_id',
            'targetForeignKey' => 'competency_id',
            'through' => 'Staff.StaffAppraisalsCompetencies',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->addBehavior('AcademicPeriod.AcademicPeriod');

        $this->toggle('remove', false);
        $this->toggle('edit', false);
        $this->toggle('add', false);
    }

    private function setupTabElements()
    {
        $options['type'] = 'staff';
        $userId = $this->Auth->user('id');
        if (!is_null($userId)) {
            $options['user_id'] = $userId;
        }

        $tabElements = $this->controller->getProfessionalDevelopmentTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('modified_user_id',    ['visible' => ['index' => true, 'view' => true], 'after' => 'final_rating']);
        $this->field('modified',            ['visible' => ['index' => true, 'view' => true], 'after' => 'modified_user_id']);

        $this->setupTabElements();
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('comment', ['visible' => false]);
        $this->field('academic_period_id', ['visible' => false]);
        $this->field('competency_set_id', ['visible' => false]);
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['visible' => false]);

        $this->setFieldOrder(['staff_appraisal_type_id', 'title', 'from', 'to', 'final_rating']);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        //Add controls filter to index page
        $academicPeriodList = $this->AcademicPeriods->getYearList(['isEditable'=>true]);
        $selectedAcademicPeriod = !is_null($this->request->query('academic_period')) ? $this->request->query('academic_period') : $this->AcademicPeriods->getCurrent();

        $extra['elements']['controls'] = ['name' => 'Institution.StaffAppraisals/controls', 'data' => [], 'options' => [], 'order' => 1];
        $this->controller->set(compact('academicPeriodList', 'selectedAcademicPeriod'));
        $query->where([$this->aliasField('academic_period_id') => $selectedAcademicPeriod]);
    }

    public function viewEditBeforeQuery(Event $event, Query $query)
    {
        $query->contain(['Competencies']);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
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
        // End

        $this->setupFields($entity);
    }

    // to rename the field header
    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'staff_appraisal_type_id') {
            return __('Type');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function onGetModifiedUserId(Event $event, Entity $entity)
    {
        if ($this->action == 'index') {
            $entity['modified_user'] = !empty($entity['modified_user']) ? $entity['modified_user'] : $entity['created_user'];
        }
    }

    public function onGetModified(Event $event, Entity $entity)
    {
        if ($this->action == 'index') {
            $entity['modified'] = date('d-M-Y', strtotime(!empty($entity['modified']) ? $entity['modified'] : $entity['created']));
        }
    }

    public function onGetCustomRatingElement(Event $event, $action, $entity, $attr, $options=[])
    {
        $staffAppraisalId = $entity->id;

        if ($action == 'view') {
            $tableHeaders = [
                $this->getMessage('Staff.Appraisal.competencies_goals'),
                $this->getMessage('Staff.Appraisal.rating')
            ];
            $tableCells = [];

            $competencyItems = $this
                ->find()
                ->contain(['Competencies'])
                ->where([$this->aliasField('id') => $entity->id])
                ->first()->competencies;

            foreach ($competencyItems as $key => $obj) {
                $rowData = [];
                $rowData[] = $obj->name;
                $rowData[] = $obj->_joinData->rating;

                $tableCells[] = $rowData;
            }

            $attr['tableHeaders'] = $tableHeaders;
            $attr['tableCells'] = $tableCells;
            $attr['finalRating'] = $entity->final_rating;

            if ($entity->has('competency_set_id')) {
                return $event->subject()->renderElement('Staff.Staff/competency_table', ['attr' => $attr]);
            }
        }

        if ($entity->has('competency_set_id')) {
            return $event->subject()->renderElement('Staff.Staff/competency_table', ['attr' => $attr]);
        }
    }

    public function setupFields(Entity $entity)
    {
        $this->field('academic_period_id');
        $this->field('from');
        $this->field('to');
        $this->field('staff_appraisal_type_id', ['type' => 'select']);
        $this->field('competency_set_id');
        $this->field('rating', ['type' => 'custom_rating']);
        $this->field('final_rating', ['type' => 'hidden']);
                $this->field('file_name', [
            'type' => 'hidden',
            'visible' => ['view' => false, 'edit' => true]
        ]);
        $this->field('file_content', ['visible' => ['view' => false, 'edit' => true]]);

        $this->setFieldOrder(['title', 'academic_period_id', 'from', 'to', 'staff_appraisal_type_id', 'competency_set_id', 'rating', 'final_rating', 'comment']);
    }
}
