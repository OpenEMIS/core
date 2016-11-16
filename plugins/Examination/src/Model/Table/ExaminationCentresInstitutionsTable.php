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

class ExaminationCentresInstitutionsTable extends ControllerActionTable {

    private $examCentreId = null;

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('ExaminationCentres', ['className' => 'Examination.ExaminationCentres']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
    	if ($entity->isNew()) {
    		$hashString = $entity->examination_centre_id . ',' . $entity->institution_id;
            $entity->id = Security::hash($hashString, 'sha256');
    	}
    }
}
