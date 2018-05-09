<?php
namespace Scholarship\Controller;

use App\Controller\AppController;
use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\ORM\Query;
use Cake\Utility\Inflector;

class ScholarshipsController extends AppController
{
    public function initialize()
    {
        parent::initialize();

    }

    public function Scholarships()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Scholarship.Scholarships']);
    }
    public function Applications()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Scholarship.Applications']);
    }
    // public function General() {
    //     $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Scholarship.General']);
    // }
    public function Identities()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.Identities']);
    }
    public function Nationalities()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.UserNationalities']);
    }
    public function Contacts()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.Contacts']);
    }
    public function Guardians()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.Guardians']);
    }
    public function StaffQualifications()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.Qualifications']);
    }

    public function onInitialize(Event $event, Table $model, ArrayObject $extra)
    {
        $this->Navigation->addCrumb('Scholarship',  ['plugin' => 'Scholarship', 'controller' => 'Scholarships', 'action' => 'index']);
        $alias = ($model->alias == 'ScholarshipApplications') ? 'Applications' : $model->alias;
        $this->Navigation->addCrumb($alias);
        $header = '';
        // if ($model instanceof \App\Model\Table\ControllerActionTable) { // CAv4

        //     $alias = $model->alias();
        //     $excludedModel = ['Applications', 'Scholarships'];

        //     if (!in_array($alias, $excludedModel)) {
        //         $model->toggle('add', false);
        //         $model->toggle('edit', false);
        //         $model->toggle('remove', false);
        //     }
        // }
        
        // $header = __('Scholarships');
        // $alias = 'Applicants';
        
        // if (array_key_exists('queryString', $this->request->query)) {
        //     $ids = $this->ControllerAction->paramsDecode($this->request->query['queryString']);
            
        //     if(isset($ids['applicant_id'])) {
        //         $applicantId = $this->ControllerAction->getQueryString('applicant_id');
        //         $alias = ($model->alias == 'ScholarshipApplications') ? 'Overview' : $model->alias;
        //         $entity = $this->Applications->Applicants->get($applicantId);
        //         $header = $entity->name;
        //         $this->Navigation->addCrumb($header);
        //         $this->Navigation->addCrumb($model->getHeader($alias));
        //     }
        // } 

        // $header .= ' - ' . $model->getHeader($alias);
        // $this->set('contentHeader', $header);

        $persona = false;
        $event = new Event('Model.Navigation.breadcrumb', $this, [$this->request, $this->Navigation, $persona]);
        $event = $model->eventManager()->dispatch($event);
    }

    public function beforeQuery(Event $event, Table $model, Query $query, ArrayObject $extra)
    {
        if (array_key_exists('queryString', $this->request->query)) {
            $applicantId = $this->ControllerAction->getQueryString('applicant_id');

            if ($model->hasField('security_user_id')) {
                $query->where([$model->aliasField('security_user_id') => $applicantId]);
            } else if ($model->hasField('student_id')) {
                $query->where([$model->aliasField('student_id') => $applicantId]);
            }
        }
    }
  
    public function getScholarshipTabElements($options = [])
    {
        if (array_key_exists('queryString', $this->request->query)) {
            $queryString = $this->request->query('queryString');
        }


        $tabElements = [
            'Applications' => [
                'url' => ['plugin' => 'Scholarship', 'controller' => 'Scholarships', 'action' => 'Applications', 'view', $queryString, 'queryString' => $queryString],
                'text' => __('Overview')
            ],
            'Identities' => [
                'url' => ['plugin' => 'Scholarship', 'controller' => 'Scholarships', 'action' => 'Identities', 'index', 'queryString' => $queryString],
                'text' => __('Identities')
            ],
            'UserNationalities' => [
                'url' => ['plugin' => 'Scholarship', 'controller' => 'Scholarships', 'action' => 'Nationalities', 'index', 'queryString' => $queryString],
                'text' => __('Nationalities')
            ],
            'Contacts' => [
                'url' => ['plugin' => 'Scholarship', 'controller' => 'Scholarships', 'action' => 'Contacts', 'index', $queryString, 'queryString' => $queryString],
                'text' => __('Contacts')
            ],
            'Guardians' => [
                'url' => ['plugin' => 'Scholarship', 'controller' => 'Scholarships', 'action' => 'Guardians', 'index', $queryString, 'queryString' => $queryString],
                'text' => __('Guardians')
            ],
            'Histories' => [
                'url' => ['plugin' => 'Scholarship', 'controller' => 'ScholarshipHistories', 'action' => 'index', 'queryString' => $queryString],
                'text' => __('Scholarship History')
            ],
            'InstitutionChoices' => [
                'url' => ['plugin' => 'Scholarship', 'controller' => 'ScholarshipApplicationInstitutionChoices', 'action' => 'index', 'queryString' => $queryString],
                'text' => __('Institution Choices')
            ],
            'ApplicationAttachments' => [
                'url' => ['plugin' => 'Scholarship', 'controller' => 'ScholarshipApplicationAttachments', 'action' => 'index', 'queryString' => $queryString],
                'text' => __('Attachments')
            ],
     
        ];

       return $this->TabPermission->checkTabPermission($tabElements);
    }
}
