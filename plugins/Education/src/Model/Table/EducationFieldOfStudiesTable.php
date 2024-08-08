<?php
namespace Education\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use App\Model\Table\ControllerActionTable;

class EducationFieldOfStudiesTable extends ControllerActionTable
{
    public function initialize(array $config): void
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
        $connection = $this->getConnection(); //POCOR-8495
        $connection->getDriver()->enableAutoQuoting();
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        // POCOR-4079 if no manual sorting, will be sort by order.
        $requestQuery = $this->request->getQuery();

        $sortList = ['name', 'ProgrammeOrientations.name'];
        if (array_key_exists('sortWhitelist', $extra['options'])) {
            $sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
        }
        $extra['options']['sortWhitelist'] = $sortList;


        $sortable = isset($requestQuery['sort']) ? true : false;

        if (!$sortable) {
            $query->find('order');
        }
    }

    public function findAvailableFieldOfStudyOptionList(Query $query, array $options)
    {
        $scholarshipId = isset($options['scholarship_id']) ? $options['scholarship_id'] : 0;

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

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'name') {
            return __('Name');
        } elseif ($field == 'code') {
            return __('Code');
        } elseif ($field == 'visible') {
            return __('Visible');
        } elseif ($field == 'education_programme_orientation_id') {
            return __('Education Programme Orientation');
        } elseif ($field == 'modified_user_id') {
            return __('Modified By');
        } elseif ($field == 'modified') {
            return __('Modified On');
        } elseif ($field == 'created_user_id') {
            return __('Created By');
        } elseif ($field == 'created') {
            return __('Created On');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    //POCOR-8495 --start
    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function beforeDelete(Event $event, Entity $entity)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }
    //POCOR-8495 --end
}
