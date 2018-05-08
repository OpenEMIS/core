<?php
namespace Scholarship\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\Network\Request;

use App\Model\Table\AppTable;

class ApplicationAttachmentsTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('scholarship_application_attachments');
        parent::initialize($config);

        $this->belongsTo('ScholarshipApplications', ['className' => 'Scholarship.ScholarshipApplications', 'foreignKey' => ['scholarship_id', 'applicant_id']]);
        $this->belongsTo('Applicants', ['className' => 'User.Users', 'foreignKey' => 'applicant_id']);
        $this->belongsTo('Scholarships', ['className' => 'Scholarship.Scholarships']);
        $this->belongsTo('ScholarshipAttachmentTypes', ['className' => 'Scholarship.ScholarshipAttachmentTypes']);
        
        $this->addBehavior('Page.FileUpload', [
            'fieldMap' => ['file_name' => 'file_content'],
            'size' => '2MB'
        ]);
    }

    public function findIndex(Query $query, array $options)
    {   
        $query
            ->contain([
                'ModifiedUser',
                'CreatedUser',
                'ScholarshipApplications',
                'Applicants',
                'Scholarships',
                'ScholarshipAttachmentTypes'
            ]);
        
        return $query;
    }

}
