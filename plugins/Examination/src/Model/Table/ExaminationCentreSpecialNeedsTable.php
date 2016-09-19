<?php
namespace Examination\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use ArrayObject;

class ExaminationCentreSpecialNeedsTable extends ControllerActionTable {
    public function initialize(array $config)
    {
        $this->table('examination_centre_special_needs');
        parent::initialize($config);
        $this->belongsTo('SpecialNeedTypes', ['className' => 'FieldOption.SpecialNeedTypes']);
    }

    public function addEditBeforeAction(Event $event) {

    }

    public function afterAction(Event $event, ArrayObject $extra)
    {

    }
}
