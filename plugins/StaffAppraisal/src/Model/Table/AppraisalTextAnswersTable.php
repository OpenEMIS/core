<?php
namespace StaffAppraisal\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;

use App\Model\Table\AppTable;

class AppraisalTextAnswersTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('AppraisalForms', ['className' => 'StaffAppraisal.AppraisalForms', 'foreignKey' => 'appraisal_form_id']);
        $this->belongsTo('AppraisalCriterias', ['className' => 'StaffAppraisal.AppraisalCriterias', 'foreignKey' => 'appraisal_criteria_id']);
        $this->belongsTo('InstitutionStaffAppraisals', ['className' => 'Institution.InstitutionStaffAppraisals', 'foreignKey' => 'institution_staff_appraisal_id', 'joinType' => 'INNER']);
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $answer = $entity->answer;
        if (empty($answer)) {
            $this->delete($entity);
        }
    }
}
