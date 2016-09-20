<?php
namespace Examination\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use Cake\Network\Request;
use ArrayObject;
use Cake\Validation\Validator;

class ExaminationCentreSpecialNeedsTable extends ControllerActionTable {

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('SpecialNeedTypes', ['className' => 'FieldOption.SpecialNeedTypes']);
    }
}
