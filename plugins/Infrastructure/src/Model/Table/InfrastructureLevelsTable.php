<?php
namespace Infrastructure\Model\Table;

use ArrayObject;
use App\Model\Table\ControllerActionTable;

class InfrastructureLevelsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('Parents', ['className' => 'Infrastructure.InfrastructureLevels']);
        $this->hasMany('InfrastructureTypes', ['className' => 'Infrastructure.InfrastructureTypes', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionInfrastructures', ['className' => 'Institution.InstitutionInfrastructures', 'dependent' => true, 'cascadeCallbacks' => true]);
    }

    public function getFieldByCode($code, $field)
    {
        $data = $this
            ->find()
            ->where([$this->aliasField('code') => $code])
            ->first();

        return $data->{$field};
    }
}
