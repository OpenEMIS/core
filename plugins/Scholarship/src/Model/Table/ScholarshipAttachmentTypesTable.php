<?php
namespace Scholarship\Model\Table;

use Cake\ORM\Query;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class ScholarshipAttachmentTypesTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('Scholarships', ['className' => 'Scholarship.Scholarships']);
        $this->hasMany('ApplicationAttachments', ['className' => 'Scholarship.ApplicationAttachments', 'dependent' => true, 'cascadeCallbacks' => true]);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator;
    }

    public function findAttachmentTypeOptionList(Query $query, array $options)
    {
        $scholarshipId = array_key_exists('scholarship_id', $options) ? $options['scholarship_id'] : 0;

        $query->where(['scholarship_id' => $scholarshipId]);

        return parent::findOptionList($query, $options);
    }
}
