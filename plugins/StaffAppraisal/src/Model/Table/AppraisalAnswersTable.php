<?php
namespace StaffAppraisal\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;

use Cake\Log\Log;
class AppraisalAnswersTable extends AppTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->belongsTo('AppraisalForms', ['className' => 'StaffAppraisal.AppraisalForms', 'foreignKey' => 'appraisal_form_id']);
        $this->belongsTo('AppraisalCriterias', ['className' => 'StaffAppraisal.AppraisalCriterias', 'foreignKey' => 'appraisal_criteria_id']);
        $this->belongsTo('StaffAppraisals', ['className' => 'Institution.StaffAppraisals', 'foreignKey' => 'institution_staff_appraisal_id', 'joinType' => 'INNER']);
    }

    // this will be moved to a behaviour when revamping the custom fields
    public function validationDefault(Validator $validator): Validator
    {
        return $validator
            ->notEmpty('answer', null, function ($context) {
                if (array_key_exists('is_mandatory', $context['data'])) {
                    return $context['data']['is_mandatory'];
                }
                return false;
            });
    }

    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->isNew() && $entity->answer === '') {
          //  return $event->stopPropagation(); //POCOR-8688
        }
    }

    public function afterSaveCommit(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->answer === '' || is_null($entity->answer)) {
            $this->delete($entity);
        }
    }

}
