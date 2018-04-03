<?php
namespace StaffAppraisal\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class AppraisalDropdownAnswersTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('AppraisalForms', ['className' => 'StaffAppraisal.AppraisalForms', 'foreignKey' => 'appraisal_form_id']);
        $this->belongsTo('AppraisalCriterias', ['className' => 'StaffAppraisal.AppraisalCriterias', 'foreignKey' => 'appraisal_criteria_id']);
        $this->belongsTo('StaffAppraisals', ['className' => 'Institution.StaffAppraisals', 'foreignKey' => 'institution_staff_appraisal_id', 'joinType' => 'INNER']);
        $this->belongsTo('AppraisalDropdownOptions', ['className' => 'StaffAppraisal.AppraisalDropdownOptions', 'foreignKey' => 'answer']);
    }

    // this will be moved to a behaviour when revamping the custom fields
    public function validationDefault(Validator $validator)
    {
        return $validator
            ->notEmpty('answer', __('This field cannot be left empty'), function ($context) {
                if (array_key_exists('is_mandatory', $context['data'])) {
                    return $context['data']['is_mandatory'];
                }
                return false;
            });
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $answer = $entity->answer;
        if (is_null($answer)) {
            $this->delete($entity);
        }
    }
}
