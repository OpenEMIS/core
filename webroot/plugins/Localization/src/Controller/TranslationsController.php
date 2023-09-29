<?php
namespace Localization\Controller;

use Cake\Event\Event;
use Cake\Core\App;
use Cake\Cache\Cache;

class TranslationsController extends AppController
{
    private $defaultLocale = 'en';

    public function initialize()
    {
        parent::initialize();
        $this->loadComponent('RequestHandler');
        $this->Localization->autoCompile(false);
        if ($this->request->is('post') && $this->request->param('action') == 'translate') {
            $token = isset($this->request->cookies['csrfToken']) ? $this->request->cookies['csrfToken'] : '';
            $this->request->env('HTTP_X_CSRF_TOKEN', $token);
        }
    }

    public function translate()
    {
        $this->RequestHandler->renderAs($this, 'json');
        $text = $this->request->data('text');
        $translated = __($text);
        $this->set('original_text', $text);
        $this->set('translated_text', $translated);
        $this->set('_serialize', ['original_text', 'translated_text']);
    }
}
