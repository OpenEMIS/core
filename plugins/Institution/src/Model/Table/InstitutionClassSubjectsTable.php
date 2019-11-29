<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use Cake\ORM\Entity;

class InstitutionClassSubjectsTable extends AppTable
{

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
        $this->belongsTo('InstitutionSubjects', ['className' => 'Institution.InstitutionSubjects']);
        
        $this->addBehavior('Restful.RestfulAccessControl', [
            'ScheduleTimetable' => ['index']
        ]);
    }

    public function afterDelete(Event $event, Entity $entity, ArrayObject $options)
    {
        $subjectEntity = $this->InstitutionSubjects->get($entity->institution_subject_id);
        $this->InstitutionSubjects->delete($subjectEntity);
    }
    
    public function findAllSubjects(Query $query, array $options)
    {       
        $institutionClassId = $options['institution_class_id'];
        $InstitutionSubjects = TableRegistry::get('Institution.InstitutionSubjects');
        $query
            ->select([
                 $this->aliasField('id'),
                 'institution_subject_id'=>$InstitutionSubjects->aliasField('id'),
                 'institution_subject_name'=>$InstitutionSubjects->aliasField('name'),
            ])
            ->contain(['InstitutionSubjects'])
            ->where([
                $this->aliasField('institution_class_id') => $institutionClassId
            ])
            ->order([
                $InstitutionSubjects->aliasField('name')=>'DESC'
            ]);
        
        return $query;
    }
}
