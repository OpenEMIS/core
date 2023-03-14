<?php
namespace Institution\Model\Table;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\ControllerActionTable;

class UnitTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('institution_units');
        parent::initialize($config);

        //$this->hasMany('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);

        $this->addBehavior('FieldOption.FieldOption');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'ClassStudents' => ['index']
        ]);
    }

    public function getUnitOptions($institutionsId, $periodId)
    {
        $institutionClasses = TableRegistry::get('institution_units');
        $query = $institutionClasses->find('list',['keyField' => 'id', 'valueField' => 'name']);
        return $query;
    }
    public function findUnitOptions(Query $query, array $options)
    {
        
        $institutionId = $options['institution_id'];
        $academicPeriodId = $options['academic_period_id'];
        
        $institutionClasses = TableRegistry::get('institution_units');
        $query = $institutionClasses->find('list',['keyField' => 'id', 'valueField' => 'name']);
        return $query->toArray();
    }
}
