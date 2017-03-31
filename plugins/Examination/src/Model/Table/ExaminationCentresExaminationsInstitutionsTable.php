<?php
namespace Examination\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use Cake\Network\Request;
use ArrayObject;
use Cake\Validation\Validator;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Utility\Security;

class ExaminationCentresExaminationsInstitutionsTable extends ControllerActionTable {
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('ExaminationCentres', ['className' => 'Examination.ExaminationCentres']);
        $this->belongsTo('Examinations', ['className' => 'Examination.Examinations']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('ExaminationCentresExaminations', [
            'className' => 'Examination.ExaminationCentresExaminations',
            'foreignKey' => ['examination_centre_id', 'examination_id']
        ]);

        $this->addBehavior('CompositeKey');
    }
}
