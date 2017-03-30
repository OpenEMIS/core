<?php
namespace Examination\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use App\Model\Table\ControllerActionTable;

class ExaminationCentresExaminationsTable extends ControllerActionTable {
    public function initialize(array $config) {
        parent::initialize($config);
        $this->belongsTo('ExaminationCentres', ['className' => 'Examination.ExaminationCentres']);
        $this->belongsTo('Examinations', ['className' => 'Examination.Examinations']);

        $this->addBehavior('CompositeKey');
    }
}
