<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use App\Model\Table\ControllerActionTable;

class InstitutionCompetencyItemCommentsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('Students', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('CompetencyTemplates', ['className' => 'Competency.CompetencyTemplates', 'foreignKey' => ['competency_template_id', 'academic_period_id'], 'bindingKey' => ['id', 'academic_period_id']]);
        $this->belongsTo('CompetencyPeriods', ['className' => 'Competency.CompetencyPeriods', 'foreignKey' => ['competency_period_id', 'academic_period_id'], 'bindingKey' => ['id', 'academic_period_id']]);
        $this->belongsTo('CompetencyItems', ['className' => 'Competency.CompetencyItems', 'foreignKey' => ['competency_item_id', 'academic_period_id', 'competency_template_id'], 'bindingKey' => ['id', 'academic_period_id', 'competency_template_id']]);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);

        $this->addBehavior('CompositeKey');
    }
}
