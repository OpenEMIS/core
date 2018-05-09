<?php
namespace Scholarship\Model\Table;

use Cake\ORM\Query;
use App\Model\Table\AppTable;

class AttachmentTypesTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('scholarship_attachment_types');
        parent::initialize($config);
        $this->belongsTo('Scholarships', ['className' => 'Scholarship.Scholarships']);
        $this->hasMany('ApplicationAttachments', ['className' => 'Scholarship.ApplicationAttachments', 'dependent' => true, 'cascadeCallbacks' => true]);
    }

    public function findAttachmentTypeOptionList(Query $query, array $options)
    {
        $scholarshipId = array_key_exists('scholarship_id', $options) ? $options['scholarship_id'] : 0;

        $query->where(['scholarship_id' => $scholarshipId]);

        return parent::findOptionList($query, $options);
    }
}
