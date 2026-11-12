<?php
namespace Student\Model\Table;

use App\Model\Table\AppTable;

class CounsellingGuidanceTypesTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->setTable('counselling_guidance_types');
        parent::initialize($config);

        $this->belongsTo('Counsellings', ['className' => 'Student.Counsellings', 'foreignKey' => 'counselling_id']);
        $this->belongsTo('GuidanceTypes', ['className' => 'Student.GuidanceTypes', 'foreignKey' => 'guidance_type_id']);
    }
}
