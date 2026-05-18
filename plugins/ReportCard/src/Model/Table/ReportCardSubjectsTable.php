<?php
namespace ReportCard\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use App\Model\Table\ControllerActionTable;

class ReportCardSubjectsTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->belongsTo('ReportCards', ['className' => 'ReportCard.ReportCards']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);

        $this->addBehavior('CompositeKey');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'ReportCardComments' => ['index']
        ]);
    }

    public function findMatchingClassSubjects(Query $query, array $options)
    {
        $staffId = $options['user']['id'];
        $checkType = $options['type'];
        $staffType = $options['staffType'];
        $staffSubject = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjectStaff');
        $reportCard = TableRegistry::getTableLocator()->get('ReportCard.ReportCards');
        $reportCardId = $options['report_card_id'];
        $classId = $options['institution_class_id'];
        $InstitutionClassSubjects = TableRegistry::getTableLocator()->get('Institution.InstitutionClassSubjects');
        $InstitutionSubjects = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjects');
        $InstitutionClasses = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
        if ($options['user']['super_admin'] != 1) {
           $orWhere[$staffSubject->aliasField('staff_id')] = $staffId;
           //$orWhere[$InstitutionClasses->aliasField('staff_id')] = $staffId;//POCOR-6809 - commented condition as it's not compulsory to have same staff for class and subject
        }
        $reportCardData = $reportCard->find()
                            ->select(['education_grade_id'])
                            ->where(['id' => $reportCardId])
                            ->first();

        $educationGradeId = $reportCardData->education_grade_id ?? null; //POCOR-9681
        return $query
                ->select([
                    'education_subject_id' => $this->aliasField('education_subject_id'),
                    'code' => $this->EducationSubjects->aliasField('code'),
                    'name' => $InstitutionSubjects->aliasField('name'),
                    'id' => $InstitutionSubjects->aliasField('id'),
                    $this->EducationSubjects->aliasField('order'),
                    'staff_id' => $staffSubject->aliasField('staff_id'),//POCOR-6734
                ])
                ->innerJoinWith('EducationSubjects')
                ->innerJoin([$InstitutionSubjects->getAlias() => $InstitutionSubjects->getTable()], [
                    $InstitutionSubjects->aliasField('education_subject_id = ') . $this->aliasField('education_subject_id')
                ])->leftJoin([$staffSubject->getAlias() => $staffSubject->getTable()], [
                    $staffSubject->aliasField('institution_subject_id = ') . $InstitutionSubjects->aliasField('id'),
                ])
                ->innerJoin([$InstitutionClassSubjects->getAlias() => $InstitutionClassSubjects->getTable()], [
                    $InstitutionClassSubjects->aliasField('institution_subject_id = ') . $InstitutionSubjects->aliasField('id'),
                    $InstitutionClassSubjects->aliasField('institution_class_id = ') . $classId,
                    $InstitutionClassSubjects->aliasField('status > 0 ')
                ])
                ->leftJoin([$InstitutionClasses->getAlias() => $InstitutionClasses->getTable()], [
                    $InstitutionClasses->aliasField('id = ') . $InstitutionClassSubjects->aliasField('institution_class_id'),
                ])
                ->where([
                    $this->aliasField('report_card_id') => $reportCardId,
                    $InstitutionSubjects->aliasField('education_grade_id') => $educationGradeId //POCOR-9681
                ])
                // ->orWhere([$orWhere])
                //->group([$InstitutionSubjects->aliasField('name')]) //POCOR-9032
                ->order([$this->EducationSubjects->aliasField('order')]);
    }
}
