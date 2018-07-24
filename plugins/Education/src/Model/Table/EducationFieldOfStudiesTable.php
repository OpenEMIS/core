<?php
namespace Education\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
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
        
        $this->hasMany('ApplicationInstitutionChoices', ['className' => 'Scholarship.ApplicationInstitutionChoices']);
        
        $this->belongsToMany('EducationSubjects', [
            'className' => 'Education.EducationSubjects',
            'joinTable' => 'education_subjects_field_of_studies',
            'foreignKey' => 'education_field_of_study_id',
            'targetForeignKey' => 'education_subject_id',
            'through' => 'Education.EducationSubjectsFieldOfStudies',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->belongsToMany('Scholarships', [
            'className' => 'Scholarship.Scholarships',
            'joinTable' => 'scholarships_field_of_studies',
            'foreignKey' => 'education_field_of_study_id',
            'targetForeignKey' => 'scholarship_id',
            'through' => 'Scholarship.ScholarshipsFieldOfStudies',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->setDeleteStrategy('restrict');
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->fields['education_programme_orientation_id']['sort'] = ['field' => 'ProgrammeOrientations.name'];
    }

    public function addEditBeforeAction(Event $event) {
        $this->fields['education_programme_orientation_id']['type'] = 'select';
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        // POCOR-4079 if no manual sorting, will be sort by order.
        $requestQuery = $this->request->query;

        $sortList = ['name', 'ProgrammeOrientations.name'];
        if (array_key_exists('sortWhitelist', $extra['options'])) {
            $sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
        }
        $extra['options']['sortWhitelist'] = $sortList;


        $sortable = array_key_exists('sort', $requestQuery) ? true : false;

        if (!$sortable) {
            $query->find('order');
        }
    }

    public function findAvailableFieldOfStudyOptionList(Query $query, array $options)
    {
        $scholarshipId = array_key_exists('scholarship_id', $options) ? $options['scholarship_id'] : 0;

        $scholarshipEntity = $this->Scholarships->get($scholarshipId);
        $isSelectAll = $this->Scholarships->checkIsSelectAll($scholarshipEntity);

        if (!$isSelectAll) {
            $query
                ->matching('Scholarships', function ($q) use ($scholarshipId) {
                    return $q->where(['scholarship_id' => $scholarshipId]);
                });
        } 

        return parent::findOptionList($query, $options);
    }
}
