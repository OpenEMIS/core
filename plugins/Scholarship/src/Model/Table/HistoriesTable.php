<?php
namespace Scholarship\Model\Table;

use Cake\ORM\Query;
use App\Model\Table\AppTable;

class HistoriesTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('scholarship_applications');
        parent::initialize($config);

        $this->belongsTo('Applicants', ['className' => 'User.Users', 'foreignKey' => 'applicant_id']);
        $this->belongsTo('Scholarships', ['className' => 'Scholarship.Scholarships']);
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users', 'foreignKey' => 'assignee_id']);
        $this->hasMany('ApplicationAttachments', [
            'className' => 'Scholarship.ApplicationAttachments',
            'foreignKey' => ['applicant_id', 'scholarship_id'],
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->hasMany('ApplicationInstitutionChoices', [
            'className' => 'Scholarship.ApplicationInstitutionChoices',
            'foreignKey' => ['applicant_id', 'scholarship_id'],
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
    }

    public function findIndex(Query $query, array $options)
    {
        $scholarshipId = $options['querystring']['scholarshipId'];

        $query
            ->contain(['Scholarships.AcademicPeriods'])
            ->where([$this->aliasField('scholarship_id') . ' <> ' => $scholarshipId])
            ->order(['AcademicPeriods.name' => 'DESC']);

        return $query;
    }
}
