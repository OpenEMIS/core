<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;

use App\Model\Table\AppTable;

class StudentIndexesCriteriasTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('InstitutionStudentIndexes', ['className' => 'Institution.InstitutionStudentIndexes', 'foreignKey' => 'institution_student_index_id']);
        $this->belongsTo('IndexesCriterias', ['className' => 'Indexes.IndexesCriterias', 'foreignKey' => 'indexes_criteria_id']);
    }

    public function getValue($institutionStudentIndexId, $indexesCriteriaId)
    {
        $valueData = $this->find()
            ->where([
                $this->aliasField('institution_student_index_id') => $institutionStudentIndexId,
                $this->aliasField('indexes_criteria_id') => $indexesCriteriaId
            ])
            ->first();

        $value = '0';
        if (!empty($valueData)) {
            $value = $valueData->value;
        }

        return $value;
    }

    public function getIndexValue($value, $indexesCriteriaId)
    {
        $IndexesCriteriasData = $this->IndexesCriterias->get($indexesCriteriaId);
        $operator = $IndexesCriteriasData->operator;
        $threshold = $IndexesCriteriasData->threshold;

        $indexValue = 0;
        switch ($operator) {
        case 1: // '<'
            if ($value < $threshold) {
                $indexValue = $IndexesCriteriasData->index_value;
            } else {
               $indexValue = 0;
            }
            break;

         case 2: // '>'
            if ($value > $threshold) {
                $indexValue = $IndexesCriteriasData->index_value;
            } else {
                $indexValue = 0;
            }
            break;

            // case 3: // '='
            //  if ($absenceDay == $threshold) {
            //      $valueIndex = $absenceDay;
            //  } else {
            //      $valueIndex = 0;
            //  }
            //  break;
        }
        return $indexValue;

    }
}
