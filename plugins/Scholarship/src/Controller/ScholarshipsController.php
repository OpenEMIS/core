<?php
namespace Scholarship\Controller;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\ORM\Query;
use Cake\Utility\Inflector;

use App\Controller\AppController;

class ScholarshipsController extends AppController
{
    public function initialize()
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

    // end

    public function onInitialize(Event $event, Table $model, ArrayObject $extra)
    {
        $this->Navigation->addCrumb('Scholarships', ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Scholarships', 'index']);

        $header = __('Scholarships');
        $alias = $model->alias();
        if ($model instanceof \App\Model\Table\ControllerActionTable) { // CAv4
            $excludedModel = ['Scholarships', 'Applications', 'RecipientPaymentStructures', 'RecipientPayments'];

            if (!in_array($alias, $excludedModel)) {
                $model->toggle('add', false);
                $model->toggle('edit', false);
                $model->toggle('remove', false);

                $applicantId = $this->ControllerAction->getQueryString('applicant_id');
                $header = $this->Users->get($applicantId)->name;

                $this->Navigation->addCrumb('Applications', ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Applications', 'index']);
                $this->Navigation->addCrumb($header);
                $this->Navigation->addCrumb($model->getHeader($alias));
            }
        }

        $header .= ' - ' . $model->getHeader($alias);
        $this->set('contentHeader', $header);

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
            } else if ($model->hasField('applicant_id')) {
                $query->where([$model->aliasField('applicant_id') => $applicantId]);
            }
        }
    }
}
