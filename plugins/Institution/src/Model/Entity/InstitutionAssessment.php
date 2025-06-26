<?php
namespace Institution\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;

class InstitutionAssessment extends Entity
{
	protected $_virtual = ['subjects'];

    protected function _getSubjects() {
        $value = 0;
        if ($this->has('institution_class_subjects')) {
            $value = count($this->institution_class_subjects);
        } else {
            $grade = $this->education_grade_id;
            $class = $this->institution_class_id;
            // POCOR-9006 start
            $icst = TableRegistry::get('Institution.InstitutionClassSubjects');
            $ist = TableRegistry::get('Institution.InstitutionSubjects');
            $value = $icst
                       ->find()
                        ->select([$icst->aliasField('id'),
                            $icst->aliasField('institution_class_id'),
                            $icst->aliasField('institution_subject_id'),
                            $icst->aliasField('status'),
                            $ist->aliasField('education_subject_id')])
                        ->innerJoin([$ist->getAlias() => $ist->getTable()],
                            [$icst->aliasField('institution_subject_id') .
                                ' = ' . $ist->aliasField('id'),
                                $ist->aliasField('education_grade_id') => $grade
                                ])
                        ->where([$icst->aliasField('status') => 1,
                            $icst->aliasField('institution_class_id') => $class])
                        ->group([$ist->aliasField('education_subject_id')])
                        ->count();
            // POCOR-9006 end
        }
        return $value;
    }
}
