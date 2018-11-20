<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;

class AbsenceTypesTable extends ControllerActionTable {
    use OptionsTrait;
    public function initialize(array $config) {
        parent::initialize($config);
        $this->hasMany('StudentAbsences', ['className' => 'Institution.InstitutionStudentAbsences', 'foreignKey' =>'absence_type_id']);

        $this->addBehavior('Restful.RestfulAccessControl', [
            'OpenEMIS_Classroom' => ['index'],
            'StudentAttendances' => ['index', 'view']
        ]);
    }

    public function getCodeList() {
        return $this->find('list', [
                'keyField' => 'id',
                'valueField' => 'code'
            ])
            ->toArray();
    }

    public function getAbsenceTypeList() {
        $result = $this
            ->find('list')
            ->toArray();
        foreach ($result as $key => $value) {
            $result[$key] = __($value);
        }
        return $result;
    }

    public function findAbsenceTypeList(Query $query, array $options)
    {
        return $query
            ->select([
                $this->aliasField('id'),
                $this->aliasField('name'),
                $this->aliasField('code')
            ])
            ->formatResults(function (ResultSetInterface $results) {
                $result[] = ['id' => 0, 'name' => __('Present'), 'code' => 'PRESENT'];

                $resultList = $results->toArray();
                foreach ($resultList as $entity) {
                    $result[] = [
                        'id' => $entity->id,
                        'name' => __($entity->name),
                        'code' => $entity->code
                    ];
                }
                return $result;
            });
    }
}
