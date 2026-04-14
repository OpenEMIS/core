<?php
namespace Configuration\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;

class StudentCreationRulesTable extends ControllerActionTable //POCOR-9385: grade matrix for student creation
{
    use OptionsTrait;

    public function initialize(array $config): void
    {
        $this->setTable('student_creation_rules'); //POCOR-9385: per-grade allow flags
        parent::initialize($config);

        $this->toggle('add', false); //POCOR-9385: rows seeded by migration — no add
        $this->toggle('remove', false); //POCOR-9385: no delete

        $this->belongsTo('EducationGrades', [ //POCOR-9385: grade association
            'className' => 'Education.EducationGrades',
            'foreignKey' => 'education_grade_id',
        ]);
        $this->EducationGrades->belongsTo('EducationProgrammes', [ //POCOR-9385: programme via grade
            'className' => 'Education.EducationProgrammes',
            'foreignKey' => 'education_programme_id',
        ]);
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        //POCOR-9385: eager-load grade and programme for display, order by programme then grade
        $query->contain(['EducationGrades.EducationProgrammes'])
              ->order(['EducationGrades.education_programme_id', 'EducationGrades.order']);
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('education_grade_id', ['visible' => false]); //POCOR-9385: hide raw FK
        $this->field('education_grade', ['type' => 'string', 'label' => __('Education Grade')]); //POCOR-9385: virtual display field
        $this->field('education_programme', ['type' => 'string', 'label' => __('Education Programme')]); //POCOR-9385: virtual display field
        $this->field('allow_student_creation', ['label' => __('Allow Student Creation')]); //POCOR-9385: the toggle

        $this->setFieldOrder(['education_programme', 'education_grade', 'allow_student_creation']);
    }

    public function onGetEducationGrade(EventInterface $event, Entity $entity)
    {
        return ($entity->has('education_grade') && $entity->education_grade) //POCOR-9385: grade name
            ? $entity->education_grade->name : '';
    }

    public function onGetEducationProgramme(EventInterface $event, Entity $entity)
    {
        //POCOR-9385: programme name via grade association
        if ($entity->has('education_grade') && $entity->education_grade
            && $entity->education_grade->has('education_programme') && $entity->education_grade->education_programme) {
            return $entity->education_grade->education_programme->name;
        }
        return '';
    }

    public function onGetAllowStudentCreation(EventInterface $event, Entity $entity)
    {
        $options = $this->getSelectOptions('general.yesno'); //POCOR-9385: Yes/No display
        return $options[(int)$entity->allow_student_creation] ?? '';
    }

    public function onUpdateFieldAllowStudentCreation(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action === 'edit') { //POCOR-9385: editable toggle on edit only
            $attr['type'] = 'select';
            $attr['options'] = $this->getSelectOptions('general.yesno');
            $attr['select'] = false;
        }
        return $attr;
    }
}
