<?php
namespace Scholarship\Model\Table;

use App\Model\Table\AppTable;

class ScholarshipsScholarshipAttachmentTypesTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('Scholarships', ['className' => 'Scholarship.Scholarships']);
        $this->belongsTo('AttachmentTypes', ['className' => 'Scholarship.AttachmentTypes', 'foreignKey' => 'scholarship_attachment_type_id']);
    }
}
