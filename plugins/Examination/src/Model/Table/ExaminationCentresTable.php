<?php
namespace Examination\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use Cake\Network\Request;
use ArrayObject;

class ExaminationCentresTable extends ControllerActionTable {
    public function initialize(array $config)
    {
        $this->table('examination_centres');
        $this->belongsTo('Examinations', ['className' => 'Examination.Examinations']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        parent::initialize($config);
    }

    public function addEditBeforeAction(Event $event) {
        $this->field('academic_period_id', ['type' => 'select']);
        $this->field('examination_id', ['type' => 'select']);
        $this->field('examination_centre', ['type' => 'select']);
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $attr['options'] = $this->AcademicPeriods->getYearList(['isEditable' => true]);
            $attr['onChangeReload'] = true;
        }
        return $attr;
    }

    public function onUpdateFieldExaminations(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            if (isset($request->data[$this->alias()]['academic_period_id'])) {

            }
        }
        return $attr;
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {

    }
}
