<?php
namespace Localization\Controller;

use Cake\Event\EventInterface;
use Cake\Core\App;
use Cake\Cache\Cache;

class TranslationsController extends AppController
{
    private $defaultLocale = 'en';

    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('RequestHandler');
        $this->Localization->autoCompile(false);
        // echo "<pre>";print_r($this->request->getCookie('csrfToken'));die;
        if ($this->request->is('post') && $this->request->getAttribute('params')['action'] == 'translate') {
            $token = !empty($this->request->getCookie('csrfToken')) ? $this->request->getCookie('csrfToken') : '';
            $this->request->getEnv('HTTP_X_CSRF_TOKEN', $token);
        }
    }

    public function translate()
    {
        $this->RequestHandler->renderAs($this, 'json');
        $text = $this->request->getData('text');
        $translated = __($text);
        $this->set('original_text', $text);
        $this->set('translated_text', $translated);
        $this->set('_serialize', ['original_text', 'translated_text']);
    }
}
