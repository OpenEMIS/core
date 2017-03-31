<?php
namespace Examination\Model\Table;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\Utility\Security;
use Cake\Event\Event;

use App\Model\Table\AppTable;

class ExaminationCentresExaminationsInvigilatorsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('ExaminationCentres', ['className' => 'Examination.ExaminationCentres']);
        $this->belongsTo('Examinations', ['className' => 'Examination.Examinations']);
		$this->belongsTo('Invigilators', ['className' => 'User.Users']);
        $this->belongsTo('ExaminationCentresExaminations', [
            'className' => 'Examination.ExaminationCentresExaminations',
            'foreignKey' => ['examination_centre_id', 'examination_id']
        ]);
        $this->hasMany('ExaminationCentreRoomsExaminationsInvigilators', [
            'className' => 'Examination.ExaminationCentreRoomsExaminationsInvigilators',
            'foreignKey' => ['examination_centre_id', 'examination_id', 'invigilator_id'],
            'bindingKey' => ['examination_centre_id', 'examination_id', 'invigilator_id'],
            'dependent' => true,
            'cascadeCallBack' => true
        ]);

        $this->addBehavior('CompositeKey');
	}
}
