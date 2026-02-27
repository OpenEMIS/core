<?php
namespace App\Controller;

use Cake\Event\EventInterface;
use Cake\Utility\Inflector;

class LocaleContentsController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Paginator');
        $this->Locales = $this->fetchTable('Locales');
        $this->LocaleContents = $this->fetchTable('LocaleContents');
        $this->LocaleContentTranslations = $this->fetchTable('LocaleContentTranslations');
    }

    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);
        $name = $this->name;
        $action  = $this->request->getParam('action');
        $actionName = __(Inflector::humanize($action));
        $header = $name .' - '.$actionName;
        $this->Navigation->addCrumb(__($name), ['plugin' => $this->getPlugin(), 'controller' => $this->getName(), 'action' => $action]);
        $this->Navigation->addCrumb($actionName);
        $this->set('contentHeader', $header);
        $this->set('selectedAction', $this->request->getParam('action'));

    }

    public function LocaleContents()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'System.LocaleContentsLanguage']);
    }
}
