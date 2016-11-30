<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;

// use App\Model\Table\ControllerActionTable;
use App\Model\Table\AppTable;


class StudentIndexesCriteriasTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('InstitutionStudentIndexes', ['className' => 'Institution.InstitutionStudentIndexes', 'foreignKey' => 'institution_student_index_id']);
        $this->belongsTo('IndexesCriterias', ['className' => 'Indexes.IndexesCriterias', 'foreignKey' => 'indexes_criteria_id']);
    }

    // public function beforeSave(Event $event, Entity $entity, ArrayObject $data)
    // {
    //     if ($entity->isNew()) {
    //         $entity->id = Text::uuid();
    //     }
    // }
}
