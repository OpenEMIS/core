<?php
namespace Education\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Event\Event;

use App\Model\Table\ControllerActionTable;

class EducationFieldOfStudiesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->addBehavior('Education.Setup');
        $this->belongsTo('ProgrammeOrientations', ['className' => 'Education.EducationProgrammeOrientations', 'foreignKey' => 'education_programme_orientation_id']);
        $this->hasMany('EducationProgrammes', ['className' => 'Education.EducationProgrammes', 'cascadeCallbacks' => true]);
        $this->hasMany('StaffQualifications', ['className' => 'Staff.Qualifications', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('QualificationSpecialisations', ['className' => 'FieldOption.QualificationSpecialisations', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->belongsToMany('EducationSubjects', [
            'className' => 'Education.EducationSubjects',
            'joinTable' => 'education_subjects_field_of_studies',
            'foreignKey' => 'education_field_of_study_id',
            'targetForeignKey' => 'education_subject_id',
            'through' => 'Education.EducationSubjectsFieldOfStudies',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->setDeleteStrategy('restrict');
    }

    public function addEditBeforeAction(Event $event) {
        $this->fields['education_programme_orientation_id']['type'] = 'select';
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        // POCOR-4079 if no manual sorting, will be sort by order.
        $requestQuery = $this->request->query;
        $sortable = array_key_exists('sort', $requestQuery) ? true : false;

        if (!$sortable) {
            $query->find('order');
        }
    }
}
