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

        // POCOR-7538 points 3.4/3.5/3.7: the Comments subject tabs follow the same rule as
        // the Subjects page - "Access All Subjects" (role flag) or the existing "All Comments"
        // permission shows every subject of the class; otherwise only subjects the user
        // teaches, plus every subject of a class they are homeroom/secondary teacher of.
        // Previously a user holding the Homeroom Teacher role got every subject tab on any
        // class, including classes where they only teach one subject.
        if ($options['user']['super_admin'] != 1 && !$this->canSeeAllCommentSubjects($staffId, $classId)) {
            $query->where($InstitutionClasses->getTeacherSubjectConditions($staffId, $InstitutionSubjects->aliasField('id')));
        }

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

    // POCOR-7538: true when one of the user's roles in the class's institution has the
    // "Access All Subjects" flag or Report Cards > All Comments view.
    private function canSeeAllCommentSubjects($staffId, $classId)
    {
        $class = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses')->find()
            ->select(['institution_id'])
            ->where(['id' => $classId])
            ->enableHydration(false)
            ->first();
        if (empty($class)) {
            return false;
        }
        $roles = TableRegistry::getTableLocator()->get('Institution.Institutions')->getInstitutionRoles($staffId, $class['institution_id']);
        if (empty($roles)) {
            return false;
        }
        if (TableRegistry::getTableLocator()->get('Security.SecurityRoles')->hasAccessAllSubjects($roles)) {
            return true;
        }
        $SecurityRoleFunctions = TableRegistry::getTableLocator()->get('Security.SecurityRoleFunctions');
        return $SecurityRoleFunctions->find()
            ->innerJoin(['sf' => 'security_functions'], ['sf.id = ' . $SecurityRoleFunctions->aliasField('security_function_id')])
            ->where([
                'sf.name' => 'All Comments',
                'sf.category' => 'Report Cards',
                $SecurityRoleFunctions->aliasField('security_role_id IN') => array_values($roles),
                $SecurityRoleFunctions->aliasField('_view') => 1,
            ])
            ->count() > 0;
    }
}
