<?php
namespace Competency\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Utility\Text;

use App\Model\Table\AppTable;

class ItemsPeriodsTable extends AppTable {

    public function initialize(array $config) {
        $this->table('competency_items_periods');
        parent::initialize($config);
        $this->belongsTo('Items',       ['className' => 'Competency.Items', 'dependent' => true]);
        $this->belongsTo('Periods',     ['className' => 'Competency.Periods', 'dependent' => true]);

        // $this->addBehavior('Restful.RestfulAccessControl', [
        //     'Results' => ['index']
        // ]);
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        // pr($entity);die;
        if ($entity->isNew()) {
            $entity->id = Text::uuid();
        }
    }

    // public function getAssessmentGradeTypes($assessmentId) {
    //     $gradeTypes = $this->find('list', [
    //             'keyField' => 'period_id',
    //             'groupField' => 'subject_id',
    //             'valueField' => 'type'
    //         ])
    //         ->matching('AssessmentGradingTypes')
    //         ->select([
    //             'period_id' => $this->aliasField('assessment_period_id'),
    //             'subject_id' => $this->aliasField('education_subject_id'),
    //             'type' => 'AssessmentGradingTypes.result_type'
    //         ])
    //         ->where([$this->aliasField('assessment_id') => $assessmentId])
    //         ->toArray();
    //     return $gradeTypes;
    // }
}