<?php
namespace Scholarship\Controller;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\ORM\Query;
use Cake\Utility\Inflector;
use Cake\Http\ServerRequest;

use App\Controller\AppController;

class ScholarshipsController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadModel('User.Users');
        $this->loadComponent('Scholarship.ScholarshipTabs');
    }

    // CAv4
    public function Scholarships()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Scholarship.Scholarships']);
    }

    // public function UsersDirectory(){
    //     echo "asdfas";die;
    // }

    public function Applications()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Scholarship.Applications']);
    }

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

    public function Histories()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Scholarship.Histories']);
    }

    public function RecipientPaymentStructures()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Scholarship.RecipientPaymentStructures']);
    }

    public function RecipientPayments()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Scholarship.RecipientPayments']);
    }

    public function RecipientCollections()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Scholarship.RecipientCollections']);
    }

    public function ScholarshipRecipients()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Scholarship.ScholarshipRecipients']);
    
    }

    // end

    public function onInitialize(Event $event, Table $model, ArrayObject $extra)
    {
        $this->Navigation->addCrumb('Scholarships', ['plugin' => $this->getPlugin(), 'controller' => $this->getName(), 'action' => 'Scholarships', 'index']);
        $header = __('Scholarships');

        $pass = $this->request->getParam('pass');
        if (isset($pass[0]) && $pass[0] == 'download') {
            return true;
        }
    
        $alias = $model->getAlias();
        if ($model instanceof \App\Model\Table\ControllerActionTable) { // CAv4
            $excludedModel = ['Scholarships','ScholarshipRecipients', 'Applications', 'RecipientPaymentStructures', 'RecipientPayments'];
            
            if (!in_array($alias, $excludedModel)) {

                $model->toggle('add', false);
                $model->toggle('edit', false);
                $model->toggle('remove', false);

                /*$queryString = $this->request->getQuery('queryString');
                echo "<pre>"; print_r($queryString);die;*/
                $queryString = $this->getQueryString();
           //     echo "<pre>"; print_r($queryString); die;
                if(isset($queryString)){
                    $applicantId = $this->getQueryString('applicant_id');
                    $header = $this->Users->get($applicantId)->name;

                    $this->Navigation->addCrumb('Applications', ['plugin' => $this->getPlugin(), 'controller' => $this->getName(), 'action' => 'Applications', 'index']);
                    $this->Navigation->addCrumb($header);
                    $this->Navigation->addCrumb($model->getHeader($alias));
                }
            }
        }
        if ($model instanceof \App\Model\Table\ControllerActionTable) { // CAv4
            $includedModel = ['Applications', 'InstitutionChoices', 'InstitutionApplicationAttachment'];

            if (in_array($alias, $includedModel)) {
                $model->toggle('add', true);
                $model->toggle('edit', true);
                $model->toggle('remove', true);

                /*$queryString = $this->request->getQuery('queryString');
                echo "<pre>"; print_r($queryString);die;*/
                $queryString = $this->getQueryString();
                if(isset($queryString)){
                    $applicantId = $this->getQueryString('applicant_id');
                    $header = $this->Users->get($applicantId)->name;

                    $this->Navigation->addCrumb('Applications', ['plugin' => $this->getPlugin(), 'controller' => $this->getName(), 'action' => 'Applications', 'index']);
                    $this->Navigation->addCrumb($header);
                    $this->Navigation->addCrumb($model->getHeader($alias));
                }
            }
        }


        $header .= ' - ' . $model->getHeader($alias);
        $this->set('contentHeader', $header);

        $persona = true;
        $event = new Event('Model.Navigation.breadcrumb', $this, [$this->request, $this->Navigation, $persona]);
        $event = $model->getEventManager()->dispatch($event);
    }

    public function beforeQuery(Event $event, Table $model, Query $query, ArrayObject $extra)
    {
        $request = $this->getQueryString();
        if (!is_null($request)) {
            $applicantId = $this->getQueryString('applicant_id');

            if ($model->hasField('security_user_id')) {
                $query->where([$model->aliasField('security_user_id') => $applicantId]);
            } else if ($model->hasField('student_id')) {
                $query->where([$model->aliasField('student_id') => $applicantId]);
            } else if ($model->hasField('applicant_id')) {
                $query->where([$model->aliasField('applicant_id') => $applicantId]);
            }
        }
    }

    public function beforeFilter(Event|\Cake\Event\EventInterface $event)
    {
        if ($this->getPlugin() == 'Scholarship') {
            $this->Security->setConfig('validatePost', false);
        }
    }

    public function ScholarshipApplicationInstitutionChoices()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Scholarship.InstitutionChoices']);
    }

    public function ScholarshipApplicationAttachments()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Scholarship.InstitutionApplicationAttachment']);
    }
}
