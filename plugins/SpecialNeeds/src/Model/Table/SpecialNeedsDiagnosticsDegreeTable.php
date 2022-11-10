<?php
namespace SpecialNeeds\Model\Table;

use ArrayObject;
use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

/**
 * Class is to create new tab dignosis in Special needs
 * @author Ehteram Ahmad <ehteram.ahmad@mail.valuecoders.com>
 * @ticket POCOR-6873
 */

class SpecialNeedsDiagnosticsDegreeTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('SpecialNeedsDiagnosticsTypes', [
            'foreignKey' => 'special_needs_diagnostics_types_id',
            'joinType' => 'INNER',
            'className' => 'SpecialNeeds.SpecialNeedsDiagnosticsTypes'
        ]);
        $this->addBehavior('FieldOption.FieldOption');
        $this->hasMany('SpecialNeedsDiagnostics', ['className' => 'SpecialNeeds.SpecialNeedsDiagnostics', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'DiagnosticsDegree' => ['index', 'view']
        ]);
    }

    private function setupFields($entity = null)
    {
        $SpecialNeedsDiagnosticsTypesTable = TableRegistry::get('SpecialNeeds.SpecialNeedsDiagnosticsTypes');
        $SpecialNeedsDiagnosticsTypesOptions = $SpecialNeedsDiagnosticsTypesTable->getDiagnosticsTypeList();
        $this->field('name');
        $this->field('default', ['entity' => $entity]);
        $this->field('international_code');
        $this->field('national_code');
        $this->field('special_needs_diagnostics_types_id', ['type' => 'select', 'options' => $SpecialNeedsDiagnosticsTypesOptions]);

        $this->setFieldOrder(['special_needs_diagnostics_types_id', 'name', 'default', 'international_code', 'national_code']);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->setFieldOrder(['special_needs_diagnostics_types_id', 'name', 'default', 'international_code', 'national_code']);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function getDegreeList($degreeId)
    {

        $data = $this
            ->find('list')
            ->where([$this->aliasField('special_needs_diagnostics_types_id') => $degreeId])
            ->toArray();
        return $data;
    }
}
