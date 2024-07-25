<?php
namespace Cake\Controller\Component;

use Cake\Controller\Component;
use Cake\Http\ServerRequest;
use Cake\Http\Response;
use Cake\Utility\Security;
use Cake\Http\Cookie\Cookie;
use Cake\Utility\Hash;
use DateTime;

class CookieComponent extends Component
{
    use \Cake\Utility\CookieCryptTrait;

    protected $_defaultConfig = [
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'key' => null,
        'httpOnly' => false,
        'encryption' => 'aes',
        'expires' => '+1 month',
    ];

    protected $_keyConfig = [];
    protected $_values = [];
    protected $_loaded = [];
    protected $_response = null;
    protected $_request = null;

    public function initialize(array $config): void
    {
        parent::initialize($config);

        if (!$this->_config['key']) {
            $this->setConfig('key', Security::getSalt());
        }

        $controller = $this->_registry->getController();
        if ($controller !== null) {
            $this->_response = $controller->getResponse();
            $this->_request = $controller->getRequest();
        } else {
            $this->_request = ServerRequest::createFromGlobals();
            $this->_response = new Response();
        }

        if (empty($this->_config['path'])) {
            $this->setConfig('path', $this->_request->getAttribute('webroot'));
        }
    }

    public function configKey($keyname, $option = null, $value = null)
    {
        if ($option === null) {
            $default = $this->_config;
            $local = isset($this->_keyConfig[$keyname]) ? $this->_keyConfig[$keyname] : [];
            return $local + $default;
        }
        
        if (!is_array($option)) {
            $option = [$option => $value];
        }

        $this->_keyConfig[$keyname] = $option;
        return null;
    }

    public function implementedEvents(): array
    {
        return [];
    }

    public function write($key, $value = null)
    {
        if (!is_array($key)) {
            $key = [$key => $value];
        }

        $keys = [];
        foreach ($key as $name => $value) {
            $this->_load($name);
            $this->_values = Hash::insert($this->_values, $name, $value);
            $parts = explode('.', $name);
            $keys[] = $parts[0];
        }

        foreach ($keys as $name) {
            $this->_write($name, $this->_values[$name]);
        }
    }

    public function read($key = null)
    {
        $this->_load($key);
        return Hash::get($this->_values, $key);
    }

    protected function _load($key)
    {
        $parts = explode('.', $key);
        $first = array_shift($parts);
        if (isset($this->_loaded[$first])) {
            return;
        }

        if (!isset($this->_request->getCookieParams()[$first])) {
            return;
        }

        $cookie = $this->_request->getCookie($first);
        $config = $this->configKey($first);
        $this->_loaded[$first] = true;
        $this->_values[$first] = $this->_decrypt($cookie, $config['encryption'], $config['key']);
    }

    public function check($key = null)
    {
        if (empty($key)) {
            return false;
        }

        return $this->read($key) !== null;
    }

    public function delete($key)
    {
        $this->_load($key);
        $this->_values = Hash::remove($this->_values, $key);
        $parts = explode('.', $key);
        $top = $parts[0];

        if (isset($this->_values[$top])) {
            $this->_write($top, $this->_values[$top]);
        } else {
            $this->_delete($top);
        }
    }

    protected function _write($name, $value)
    {
        $config = $this->configKey($name);
        $expires = new DateTime($config['expires']);
        $cookie = new Cookie(
            $name,
            $this->_encrypt($value, $config['encryption'], $config['key']),
            $expires,
            $config['path'],
            $config['domain'],
            $config['secure'],
            $config['httpOnly']
        );
        
        $this->_response = $this->_response->withCookie($cookie);
    }

    protected function _delete($name)
    {
        $config = $this->configKey($name);
        $expires = new DateTime('now');
        $cookie = new Cookie(
            $name,
            '',
            $expires->modify('-1 year'),
            $config['path'],
            $config['domain'],
            $config['secure'],
            $config['httpOnly']
        );
        
        $this->_response = $this->_response->withCookie($cookie);
    }

    protected function _getCookieEncryptionKey(): string
    {
        return (string)$this->_config['key'];
    }
}
