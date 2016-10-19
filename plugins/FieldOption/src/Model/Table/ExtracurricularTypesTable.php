<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class ExtracurricularTypesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('extracurricular_types');
        parent::initialize($config);

        $this->hasMany('StaffExtracurricular', ['className' => 'Staff.Extracurriculars', 'foreignKey' => 'extracurricular_type_id']);
        $this->hasMany('StudentExtracurricular', ['className' => 'Student.Extracurriculars', 'foreignKey' => 'extracurricular_type_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }
}
