<?php
namespace Scholarship\Model\Table;

use Cake\ORM\Query;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class ScholarshipHistoriesTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('scholarship_applications');
        parent::initialize($config);

        $this->belongsTo('Applicants', ['className' => 'Security.Users', 'foreignKey' => 'applicant_id']);
        $this->belongsTo('Scholarships', ['className' => 'Scholarship.Scholarships']);
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->hasMany('InstitutionChoices', ['className' => 'Scholarship.InstitutionChoices','dependent' => true, 'cascadeCallbacks' => true]);
        // // $this->hasMany('ApplicationAttachments', ['className' => 'Scholarship.ApplicationAttachments ', 'dependent' => true, 'cascadeCallbacks' => true]);
    }

    public function findIndex(Query $query, array $options)
    {   
        $scholarshipId = $options['querystring']['scholarshipId'];

        $query
            ->contain([
                'Applicants',
                'Statuses',
                'Scholarships.AcademicPeriods',
            ])
            ->where([$this->aliasField('scholarship_id') . ' <> ' => $scholarshipId])
            ->order(['AcademicPeriods.name' => 'DESC']);

        return $query;
    }


}
