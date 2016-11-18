<?php
namespace Examination\Model\Table;

use App\Model\Table\AppTable;
use Cake\Event\Event;
use Cake\Network\Request;
use ArrayObject;
use Cake\Validation\Validator;
use Cake\Utility\Security;
use Cake\ORM\Entity;

class ExaminationCentreSpecialNeedsTable extends AppTable {

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('ExaminationCentres', ['className' => 'Examination.ExaminationCentres']);
        $this->belongsTo('SpecialNeedTypes', ['className' => 'FieldOption.SpecialNeedTypes']);
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->isNew()) {
            $hashString = $entity->examination_centre_id . ',' . $entity->special_need_type_id;
            $entity->id = Security::hash($hashString, 'sha256');
        }
    }
}
