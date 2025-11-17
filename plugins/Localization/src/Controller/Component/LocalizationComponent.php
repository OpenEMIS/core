<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-16

OpenEMIS
Open Education Management Information System

Copyright © 2013 UNECSO.  This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by the Free Software Foundation
, either version 3 of the License, or any later version.  This program is distributed in the hope
that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE.See the GNU General Public License for more details. You should
have received a copy of the GNU General Public License along with this program.  If not, see
<http://www.gnu.org/licenses/>.  For more information please wire to contact@openemis.org.
*/

namespace Localization\Controller\Component;

use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\Event\EventInterface;
use Cake\I18n\I18n;
use Cake\Cache\Cache;
use Cake\ORM\TableRegistry;
use Cake\Core\App;
use Cake\I18n\Time;
use Cake\Filesystem\File;
use Cake\Event\EventManager;
use Cake\Event\EventDispatcherTrait;
use Cake\Http\ServerRequest;
use Cake\Http\Cookie\Cookie;
use Cake\Http\Response;

class LocalizationComponent extends Component
{
    use EventDispatcherTrait;
    private $defaultLocale = 'en';
    private $autoCompile = true;
    private $controller;
    public $Session;
    public $showLanguage = true;
    public $language = 'en';
    private $languages = [
        'ar' => ['name' => 'العربية', 'direction' => 'rtl', 'locale' => 'ar_SA'],
        'zh' => ['name' => '中文', 'direction' => 'ltr', 'locale' => 'zh_CN'],
        'en' => ['name' => 'English', 'direction' => 'ltr', 'locale' => 'en_US'],
        'fr' => ['name' => 'Français', 'direction' => 'ltr', 'locale' => 'fr_FR'],
        'ru' => ['name' => 'русский', 'direction' => 'ltr', 'locale' => 'ru_RU'],
        'es' => ['name' => 'español', 'direction' => 'ltr', 'locale' => 'es_ES']
    ];

    // Components are defined in the parent class as protected $components = []
    // We set them in initialize() method instead to avoid type declaration conflicts

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['Controller.initialize'] = 'beforeFilter';
        return $events;
    }

    // Is called before the controller's beforeFilter method.
    public function initialize(array $config): void
    {
        // Set components to avoid redeclaring the property (which causes type conflicts in CakePHP 5)
        $this->components = ['Cookie', 'Auth'];
        
        // Manually populate _componentMap since we set components after constructor
        // This is needed for __get() to work properly in CakePHP 5
        if ($this->components) {
            $this->_componentMap = $this->_registry->normalizeArray($this->components);
        }
        
        $session = $this->getController()->getRequest()->getSession();
        $lang = !empty($session->read('System.language')) ? $session->read('System.language') : 'en';
        $this->controller = $this->_registry->getController();
        
        // Access Cookie component - it will be loaded lazily via __get() magic method
        // In CakePHP 5, components are loaded when first accessed
        $this->Cookie->name = str_replace(' ', '_', $config['productName']) . '_COOKIE';
        $this->Cookie->time = 3600 * 24 * 30; // expires after one month
        // list($this->language, $this->showLanguage) = $this->detectLanguage();
        list($this->language, $this->showLanguage) = $this->detectLanguage($lang); //Version 4
        $this->Session = $session;
    }

    public function getCookie()
    {
        return $this->Cookie;
    }

    private function dispatch($subject, $eventKey, $method = null, $params = [], $autoOff = false)
    {
        $eventManager = $this->getController()->getEventManager();;
        $this->onEvent($subject, $eventKey, $method);
        $event = new Event($eventKey, $this, $params);

        $event = $subject->$eventManager->dispatch($event);
        if (!is_null($method) && $autoOff) {
            $this->offEvent($subject, $eventKey, $method);
        }
        return $event;
    }

    private function onEvent($subject, $eventKey, $method)
    {
        $eventMap = $subject->implementedEvents();
        if (!array_key_exists($eventKey, $eventMap) && !is_null($method)) {
            if (method_exists($subject, $method)) {
                $subject->getEventManager()->on($eventKey, [], [$subject, $method]);
            }
        }
    }

    private function offEvent($subject, $eventKey, $method)
    {
        $subject->getEventManager()->off($eventKey, [$subject, $method]);
    }

    /**
     *  Function to get the language to display base on the system configuration
     *
     * @return array language - Language to display, showLanguage - If the language menu is to be displayed
     */
    private function detectLanguage($changedLanguage)
    {
        // Default language
        //V4[Start]
        // $lang = $this->language;
        $session = $this->getController()->getRequest()->getSession();
        $lang = $session->read('System.language');
        //V4[End]
        $request = $this->request;
        $session = $this->getController()->getRequest()->getSession();
        $showLanguage = $this->showLanguage;
        // $lang = $this->language;
        $eventManager = $this->getController()->getEventManager();
        $event = $eventManager->dispatch($this->getController()->getName(), 'Controller.Localization.getLanguageOptions', 'getLanguageOptions', [], true);
        if ($event->getResult()) {
            if (is_array($event->getResult())) {
                list($showLanguage, $lang) = $event->getResult();
            }
        }

        // Language menu enabled
        if (!$session->read('System.language_menu')) {
            if ($this->getController()->getRequest()->getData()) {
                // $langQuery = $this->getController()->getRequest()->getData()['System']['language']; //V4 POCOR-8410
                $langQuery = $lang;
                if (isset($langQuery)) {
                    $lang = $langQuery;
                }
                $user = $this->Auth->user();
                if ($user) {
                    $event = $eventManager->dispatch($this->getController()->getName(), 'Controller.Localization.updateLoginLanguage', 'updateLoginLanguage', [$user, $lang], true);
                }
                $this->Cookie->write('System.language', $lang);
            } else if ($this->Cookie->check('System.language')) {
                $lang = $this->Cookie->read('System.language');
            } else if ($session->check('System.language')) {
                $lang = $session->read('System.language');
                $this->Cookie->write('System.language', $lang);
            } else {
                // This condition will only be reach if the user has not login and the cookie for the system language has not been set on the browser
                $this->Cookie->write('System.language', $lang);
            }
        } // Language menu disabled
        else {
            // $lang = $session->read('System.language');
            $user = $this->Auth->user();
            if ($user) {
                $event = $eventManager->dispatch($this->getController()->getName(), 'Controller.Localization.updateLoginLanguage', 'updateLoginLanguage', [$user, $lang], true);
            }
            $this->Cookie->write('System.language', $lang);
        }

        return [$lang, $showLanguage];
    }

    public function beforeFilter(EventInterface $event)
    {
        // Call to recompile the language if the translation files are affected
        //V4 POCOR-8410
        $session = $this->getController()->getRequest()->getSession();
        $this->language = !empty($session->read('System.language')) ? $session->read('System.language') : 'en';
        if ($this->autoCompile()) {
            $this->updateLocaleFile($this->language);
        }
        // Move the I18n::locale setting here so that the update can be instant
        I18n::setLocale($this->language);
    }

    public function autoCompile($compile = null)
    {
        if (is_null($compile)) {
            return $this->autoCompile;
        } else {
            $this->autoCompile = $compile;
        }
    }

    private function updateLocaleFile($lang)
    {
        if ($this->defaultLocale != $lang) {
            $isChanged = $this->isChanged($lang);
            if ($isChanged) {
                $this->convertPO($lang, $isChanged);
            }
        }
    }

    private function getModifiedDate()
    {
        $LocaleContentTranslations = TableRegistry::getTableLocator()->get('LocaleContentTranslations');

        // using modified so when new word modified able to refresh the default.po
        $lastModified = $LocaleContentTranslations
            ->find()
            ->where([$LocaleContentTranslations->aliasField('modified') . ' IS NOT NULL'])
            ->order([$LocaleContentTranslations->aliasField('modified') => 'DESC'])
            ->extract('modified')
            ->first();

        // if the table is a new table and no modified records, will be sort by created and get the created date.
        $lastCreated = $LocaleContentTranslations
            ->find()
            ->order([$LocaleContentTranslations->aliasField('created') => 'DESC'])
            ->extract('created')
            ->first();

        if (!$lastModified) {
            return $lastCreated;
        } elseif ($lastModified->lt($lastCreated)) {
            return $lastCreated;
        } else {
            return $lastModified;
        }
    }

    private function isChanged($locale)
    {
        $localeDir = current(App::path('Locale'));
        $fileLocation = $localeDir . $locale . DS . 'default.po';
        $lastModified = $this->getModifiedDate();
        // if (file_exists($fileLocation)) {
        //     $file = fopen($fileLocation, "r");
        //     while (!feof($file)) {
        //         $line = fgets($file);
        //         if (strpos($line, 'PO-Revision-Date: ')) {
        //             $line = str_replace('"PO-Revision-Date: ', '', $line);
        //             $line = str_replace('\n"', '', $line);
        //             try {
        //                 $dateTime = new Time($line);
        //                 if ($lastModified->eq($dateTime)) {
        //                     $lastModified = false;
        //                 }
        //             } catch (\Exception $e) {
        //                 // default will return last modified date
        //             }
        //             break;
        //         }
        //     }

        //     fclose($file);
        // }

        return $lastModified;
    }

    private function convertPO($locale, $lastModified)
    {
        $str = "";
        $localeDir = current(App::path('Locale'));
        $fileLocation = $localeDir . $locale . DS . 'default.po';

        $LocaleContentTranslations = TableRegistry::getTableLocator()->get('LocaleContentTranslations');
        $data = $LocaleContentTranslations
            ->find('list', [
                'keyField' => 'locale_content_en',
                'valueField' => 'translation'
            ])
            ->contain(['LocaleContents', 'Locales'])
            ->select([
                'translation',
                'locale_id',
                'locale_iso' => 'Locales.iso',
                'locale_content_id',
                'locale_content_en' => 'LocaleContents.en'
            ])
            ->where(['Locales.iso' => $locale])
            ->toArray();


        // clear persistent cache that is used for Translations
        // Cache::clear(false, '_cake_core_');
        Cache::clear('_cake_core_');

        // Header of the PO file
        $str .= 'msgid ""' . "\n";
        $str .= 'msgstr ""' . "\n";
        $str .= '"Project-Id-Version: OpenEMIS Project\n"' . "\n";
        $str .= '"POT-Creation-Date: 2013-01-17 02:33+0000\n"' . "\n";
        $str .= '"PO-Revision-Date: ' . $lastModified->format('Y-m-d H:i:sP') . '\n"' . "\n";
        $str .= '"Last-Translator: \n"' . "\n";
        $str .= '"Language-Team: \n"' . "\n";
        $str .= '"MIME-Version: 1.0\n"' . "\n";
        $str .= '"Content-Type: text/plain; charset=UTF-8\n"' . "\n";
        $str .= '"Content-Transfer-Encoding: 8bit\n"' . "\n";
        $str .= '"Language: ' . $locale . '\n"' . "\n";

        //Replace the whole file
        $file = new File($fileLocation, true);
        $file->write($str);
        foreach ($data as $key => $value) {
            $msgid = $key;
            $msgstr = $value;
            $str = "\n";
            $str .= 'msgid "' . $msgid . '"' . "\n";
            $str .= 'msgstr "' . $msgstr . '"' . "\n";
            //Append to current file
            $file->append($str);
        }
        return $file->close();
    }

    // Is called after the controller's beforeFilter method but before the controller executes the current action handler.
    public function startup(EventInterface $event)
    {
        $controller = $this->controller;
        $session = $this->getController()->getRequest()->getSession();
        $lang = $session->read('System.language');
        $htmlLang = !empty($lang) ? $lang : 'en';
        $languages = $lang;
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && array_key_exists('System', $this->getController()->getRequest()->getData())) {
            if (isset($this->getController()->getRequest()->getData()['System']['language'])) {
                $htmlLang = $this->getController()->getRequest()->getData()['System']['language'];
                // $cookie = new \Cake\Http\Cookie\Cookie(
                //             'System.language',
                //             $htmlLang
                //         );
                // $this->response = $this->response->withCookie($cookie);
                // $this->Cookie->write('System.language', $htmlLang);


                $data = [
                    'key1' => 'value1',
                    'key2' => 'value2',
                    // Add more key-value pairs as needed
                ];

                // Create a new cookie instance with serialized array data
                $cookie = new Cookie(
                    'System.language', // Cookie name
                    $htmlLang
                );

                // Get the response object
                $response = new Response();

                // Add the cookie to the response
                $response = $response->withCookie($cookie);
            }
        }

        // echo "<pre>";print_r($this->Cookie->read('System.language'));die();
        $this->Session->write('System.language', $htmlLang);

        // get direction from locales table.
        $Locales = TableRegistry::getTableLocator()->get('Locales');
        $langDir = $Locales->getLangDir($htmlLang);
        $htmlLangDir = array_key_exists($htmlLang, (array)$languages) ? $languages[$htmlLang]['direction'] : $langDir;
        $controller->set('showLanguage', $this->showLanguage);
        $controller->set('languageOptions', $this->getOptions());
        $controller->set(compact('htmlLang', 'htmlLangDir'));
    }

    public function getOptions()
    {
        $session = $this->getController()->getRequest()->getSession();
        $this->languages = $session->read('System.language');
        $languages = $this->languages;
        $options = [];

        foreach ($languages as $key => $lang) {
            $options[$key] = $lang['name'];
        }

        // new languages added.
        $Locales = TableRegistry::getTableLocator()->get('Locales');
        $localesData = $Locales->find()->all();

        foreach ($localesData as $locale) {
            if (!array_key_exists($locale->iso, $options)) {
                $options[$locale->iso] = $locale->name;
            }
        }
        // echo "<pre>";print_r($options);die;
        return $options;
    }

    public function getLanguages()
    {
        return $this->languages;
    }
}
