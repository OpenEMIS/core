<?php
namespace User\Model\Table;

use App\Model\Table\ControllerActionTable;

class LanguageProficienciesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('language_proficiencies');
        parent::initialize($config);

        $this->addBehavior('FieldOption.FieldOption');
    }
}
