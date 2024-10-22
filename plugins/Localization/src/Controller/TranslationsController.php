<?php
namespace Localization\Controller;

use Cake\Event\Event;
use Cake\Core\App;
use Cake\Cache\Cache;
use Cake\Log\Log;

class TranslationsController extends AppController
{
    private $defaultLocale = 'en';

    public function initialize(): void
    {
        Log::debug('trtr');
        parent::initialize();
        $this->loadComponent('RequestHandler');
        $this->Localization->autoCompile(false);
        // echo "<pre>";print_r($this->request->getCookie('csrfToken'));die;
        if ($this->request->is('post') && $this->request->getAttribute('params')['action'] == 'translate') {
            $token = !empty($this->request->getCookie('csrfToken')) ? $this->request->getCookie('csrfToken') : '';
            $this->request->getEnv('HTTP_X_CSRF_TOKEN', $token);
            Log::debug('trtr1');
        }
    }

    public function translate()
    {
        Log::debug('trtr2');
        $this->RequestHandler->renderAs($this, 'json');
        $text = $this->request->getData('text');
        $translated = __($text);
        $this->set('original_text', $text);
        $this->set('translated_text', $translated);
        $this->set('_serialize', ['original_text', 'translated_text']);
    }
}
