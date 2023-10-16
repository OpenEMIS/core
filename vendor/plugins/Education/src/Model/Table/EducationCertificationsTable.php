<?php
namespace Education\Model\Table;

use App\Model\Table\ControllerActionTable;

class EducationCertificationsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->addBehavior('Education.Setup');
        $this->hasMany('EducationProgrammes', ['className' => 'Education.EducationProgrammes', 'cascadeCallbacks' => true]);

        $this->setDeleteStrategy('restrict');
    }
}
