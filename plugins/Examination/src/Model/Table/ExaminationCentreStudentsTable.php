<?php
namespace Examination\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Utility\Text;
use App\Model\Table\ControllerActionTable;
use Cake\I18n\Time;
use App\Model\Traits\OptionsTrait;
use Cake\Validation\Validator;

class ExaminationCentreStudentsTable extends ControllerActionTable {
    use OptionsTrait;

    public function initialize(array $config) {
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Examinations', ['className' => 'Examination.Examinations']);
        $this->belongsTo('ExaminationCentres', ['className' => 'Examination.ExaminationCentres']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
        $this->hasMany('ExaminationItems', ['className' => 'Examination.ExaminationItems', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->belongsToMany('ExaminationCentreSpecialNeeds', ['className' => 'Examination.ExaminationCentreSpecialNeeds']);

        $this->addBehavior('User.AdvancedNameSearch');
        $this->addBehavior('Examination.RegisteredStudents');
    }

    public function validationDefault(Validator $validator) {
        $validator = parent::validationDefault($validator);
        return $validator
            ->allowEmpty('registration_number')
            ->add('registration_number', 'ruleUnique', [
                'rule' => ['validateUnique', ['scope' => ['examination_centre_id', 'education_subject_id']]],
                'provider' => 'table'
            ]);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $toolbarAttr = [
            'class' => 'btn btn-xs btn-default',
            'data-toggle' => 'tooltip',
            'data-placement' => 'bottom',
            'escape' => false
        ];
        $button['url'] = ['plugin' => 'Examination', 'controller' => 'Examinations', 'action' => 'BulkStudentRegistration', 'add'];
        $button['type'] = 'button';
        $button['label'] = '<i class="fa kd-add"></i>';
        $button['attr'] = $toolbarAttr;
        $button['attr']['title'] = __('Bulk Add');
        $extra['toolbarButtons']['bulkAdd'] = $button;
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $entity->id = Text::uuid();
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->fields['total_mark']['visible'] = false;
        $this->controller->getStudentsTab();
    }

    public function findResults(Query $query, array $options) {
        $academicPeriodId = $options['academic_period_id'];
        $examinationId = $options['examination_id'];
        $examinationCentreId = $options['examination_centre_id'];
        $educationSubjectId = $options['education_subject_id'];

        $Users = $this->Users;
        $ItemResults = TableRegistry::get('Examination.ExaminationItemResults');

        return $query
            ->select([
                $ItemResults->aliasField('id'),
                $ItemResults->aliasField('marks'),
                $ItemResults->aliasField('examination_grading_option_id'),
                $ItemResults->aliasField('academic_period_id'),
                $this->aliasField('student_id'),
                $this->aliasField('institution_id'),
                $Users->aliasField('openemis_no'),
                $Users->aliasField('first_name'),
                $Users->aliasField('middle_name'),
                $Users->aliasField('third_name'),
                $Users->aliasField('last_name'),
                $Users->aliasField('preferred_name')
            ])
            ->matching('Users')
            ->leftJoin(
                [$ItemResults->alias() => $ItemResults->table()],
                [
                    $ItemResults->aliasField('student_id = ') . $this->aliasField('student_id'),
                    $ItemResults->aliasField('academic_period_id = ') . $this->aliasField('academic_period_id'),
                    $ItemResults->aliasField('examination_id = ') . $this->aliasField('examination_id'),
                    $ItemResults->aliasField('examination_centre_id = ') . $this->aliasField('examination_centre_id'),
                    $ItemResults->aliasField('education_subject_id = ') . $this->aliasField('education_subject_id')
                ]
            )
            ->where([
                $this->aliasField('academic_period_id') => $academicPeriodId,
                $this->aliasField('examination_id') => $examinationId,
                $this->aliasField('examination_centre_id') => $examinationCentreId,
                $this->aliasField('education_subject_id') => $educationSubjectId
            ])
            ->group([
                $this->aliasField('student_id'),
                $ItemResults->aliasField('academic_period_id')
            ])
            ->order([
                $this->aliasField('student_id')
            ]);
    }
}
