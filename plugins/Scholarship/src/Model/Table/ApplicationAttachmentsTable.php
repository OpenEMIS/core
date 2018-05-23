<?php
namespace Scholarship\Model\Table;

use Cake\ORM\Query;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class ApplicationAttachmentsTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('scholarship_application_attachments');
        parent::initialize($config);

        $this->belongsTo('Applications', ['className' => 'Scholarship.Applications', 'foreignKey' => ['applicant_id', 'scholarship_id']]);
        $this->belongsTo('Applicants', ['className' => 'User.Users', 'foreignKey' => 'applicant_id']);
        $this->belongsTo('Scholarships', ['className' => 'Scholarship.Scholarships']);
        $this->belongsTo('AttachmentTypes', ['className' => 'Scholarship.AttachmentTypes', 'foreignKey' => 'scholarship_attachment_type_id']);
        
        $this->addBehavior('Page.FileUpload', [
            'fieldMap' => ['file_name' => 'file_content'],
            'size' => '2MB'
        ]);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        return $validator->requirePresence(['file_name', 'file_content']);
    }

    public function findIndex(Query $query, array $options)
    {   
        $query
            ->contain([
                'ModifiedUser',
                'CreatedUser',
                'Applications',
                'Applicants',
                'Scholarships',
                'AttachmentTypes'
            ]);
        
        return $query;
    }

}
