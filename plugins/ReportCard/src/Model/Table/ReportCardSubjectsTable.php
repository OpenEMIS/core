<?php
namespace ReportCard\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\ControllerActionTable;

class ReportCardSubjectsTable extends ControllerActionTable
{
    public function initialize(array $config)
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
        $staffSubject = TableRegistry::get('Institution.InstitutionSubjectStaff');
        $reportCardId = $options['report_card_id'];
        $classId = $options['institution_class_id'];
        $InstitutionClassSubjects = TableRegistry::get('Institution.InstitutionClassSubjects');
        $InstitutionSubjects = TableRegistry::get('Institution.InstitutionSubjects');
        $InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
        //echo "<pre>";print_r($options);die();
        if ($options['user']['super_admin'] != 1) {
           $orWhere[$staffSubject->aliasField('staff_id')] = $staffId;
           $orWhere[$InstitutionClasses->aliasField('staff_id')] = $staffId;
        }
        return $query
                ->select([
                    'education_subject_id' => $this->aliasField('education_subject_id'),
                    'code' => $this->EducationSubjects->aliasField('code'),
                    'name' => $InstitutionSubjects->aliasField('name'),
                    'id' => $InstitutionSubjects->aliasField('id'),
                    $this->EducationSubjects->aliasField('order')
                ])
                ->innerJoinWith('EducationSubjects')
                ->innerJoin([$InstitutionSubjects->alias() => $InstitutionSubjects->table()], [
                    $InstitutionSubjects->aliasField('education_subject_id = ') . $this->aliasField('education_subject_id')
                ])->leftJoin([$staffSubject->alias() => $staffSubject->table()], [
                    $staffSubject->aliasField('institution_subject_id = ') . $InstitutionSubjects->aliasField('id'),
                ])
                ->innerJoin([$InstitutionClassSubjects->alias() => $InstitutionClassSubjects->table()], [
                    $InstitutionClassSubjects->aliasField('institution_subject_id = ') . $InstitutionSubjects->aliasField('id'),
                    $InstitutionClassSubjects->aliasField('institution_class_id = ') . $classId,
                    $InstitutionClassSubjects->aliasField('status > 0 ')
                ])
                ->leftJoin([$InstitutionClasses->alias() => $InstitutionClasses->table()], [
                    $InstitutionClasses->aliasField('id = ') . $InstitutionClassSubjects->aliasField('institution_class_id'),
                ])
                ->where([
                    $this->aliasField('report_card_id') => $reportCardId
                ])
                ->orWhere([$orWhere])
                ->group([$InstitutionSubjects->alias('name')])
                ->order([$this->EducationSubjects->aliasField('order')]);
    }
}
