<?php
namespace Student\Model\Table;
use Cake\ORM\Query;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\Event\EventInterface;
use App\Model\Table\ControllerActionTable;
use ArrayObject;
use Cake\ORM\TableRegistry;

class StudentExtracurricularsTable extends ControllerActionTable {
	public function initialize(array $config): void
    {
        $this->setTable('student_extracurriculars');
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('ExtracurricularTypes', ['className' => 'FieldOption.ExtracurricularTypes']);
        $this->toggle('view', false);
        $this->toggle('edit', false);
        $this->toggle('search', true);
        $this->toggle('add', false);
        $this->toggle('remove', false);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        return $validator->add('start_date', 'ruleCompareDate', [
            'rule' => ['compareDate', 'end_date', false]
        ]);
    }

    public function beforeFind(EventInterface $event, Query $query, ArrayObject $options, $primary)
    {
        // Example handling
        if (isset($options['student_id'])) {
            $query->where(['Extracurriculars.security_user_id' => $options['student_id']]);
        }

        if (isset($options['academic_period_id'])) {
            $query->where(['Extracurriculars.academic_period_id' => $options['academic_period_id']]);
        }
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
        $selectedAcademicPeriod = $extra['academic_period_id'] ?? $this->AcademicPeriods->getCurrent();
        $conditions[$this->aliasField('academic_period_id')] = $selectedAcademicPeriod;
        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
        $extra['elements']['controls'] = ['name' => 'Student.Extracurriculars/controls', 'data' => [], 'options' => [], 'order' => 1];
    }

}
