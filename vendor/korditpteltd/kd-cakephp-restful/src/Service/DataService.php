<?php
namespace Restful\Service;

use Cake\Network\Http\Client;

class DataService
{
    private $base;
    private $version = 'v2';
    private $controller = 'restful';
    private $className;
    private $responseType = 'json';
    private $method = 'GET';
    private $id = 0;
    private $_schema = false;
    private $_action = null;
    private $_fields = [];
    private $_contain = [];
    private $_innerJoinWith = [];
    private $_finder = [];
    private $_where = [];
    private $_orWhere = [];
    private $_group = [];
    private $_order = [];
    private $_limit = 0;
    private $_page = 0;
    private $_search = '';
    private $_wildcard = '_';
    private $_db = 'default';
    private $_showBlobContent = null;
    private $_querystring = '';
    private $header = [];

    /**
     * Create a new HTTP Client.
     *
     * ### Config options
     *
     * You can set the following options when creating a data service:
     *
     * - className - The class name to perform the CRUD on.
     * - base - The base path to the data service call
     *
     * @param array $config
     */
    public function __construct($config = [])
    {
        $this->base = $config['base'];
        if (array_key_exists('className', $config)) {
            $this->className = $config['className'];
        }
        if (array_key_exists('controller', $config)) {
            $this->controller = $config['controller'];
        }
        if (array_key_exists('version', $config)) {
            $this->version = $config['version'];
        }
        if (array_key_exists('action', $config)) {
            $this->_action = $config['action'];
        }
        if (array_key_exists('header', $config)) {
            $this->header = $config['header'];
        }
        if (array_key_exists('db', $config)) {
            $this->_db = $config['db'];
        }
    }

    public function init($className, $action = 'custom')
    {
        $newDataService = new self(['className' => $className, 'base' => $this->base, 'version' => $this->version, 'action' => $action, 'header' => $this->header, 'db' => $this->_db]);
        return $newDataService;
    }

    public function setVersion($version)
    {
        $this->version = $version;
    }

    public function setDB($db)
    {
        $this->_db = $db;
    }

    public function urlsafeB64Encode($input)
    {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }

    public function setBase($base)
    {
        $this->base = $base;
    }

    public function setClassName($className)
    {
        $this->className = $className;
    }

    public function setAction($action)
    {
        $this->_action = $action;
    }

    public function schema($bool)
    {
        $this->_schema = $bool;
        return $this;
    }

    public function querystring($params)
    {
        $json = json_encode($params);
        $this->_querystring = $this->urlsafeB64Encode($json);
        return $this;
    }

    public function setController($controller)
    {
        $this->controller = $controller;
    }

    public function setHeader($header)
    {
        $this->header = $header;
    }

    public function appendHeader($name, $params)
    {
        $this->header[$name] = $params;
    }

    public function getHeaders()
    {
        return $this->header;
    }

    public function wildcard()
    {
        return $this->_wildcard;
    }

    public function reset()
    {
        $this->method = 'GET';
        $this->id = 0;
        $this->_fields = [];
        $this->_contain = [];
        $this->_finder = [];
        $this->_where = [];
        $this->_limit = 0;
        $this->_group = [];
        $this->_order = [];
        $this->_page = 0;
        $this->_schema = false;
        $this->_querystring = '';
        $this->_search = '';
    }

    public function select($fields)
    {
        if (!empty($fields)) {
            $this->_fields = $fields;
        }
        return $this;
    }

    public function get($id)
    {
        $this->id = $id;
        return $this->ajax();
    }

    public function all()
    {
        return $this->ajax();
    }

    public function contain($contain)
    {
        $this->_contain = $contain;
        return $this;
    }

    public function innerJoinWith($innerJoinWith)
    {
        $this->_innerJoinWith = $innerJoinWith;
        return $this;
    }

    public function find($finder, $params = [])
    {
        if (empty($params)) {
            $this->_finder[] = $finder;
        } else if (is_array($params)) {
            $paramsArray = [];
            foreach ($params as $key => $value) {
                $paramsArray[] = $key . ':' . $value;
            }
            $this->_finder[] = $finder . '[' . implode(';', $paramsArray) . ']';
        }
        return $this;
    }

    public function where($where)
    {
        $returnArr = [];
        foreach ($where as $key => $value) {
            $returnArr[str_replace('.', '-', $key)] = $value;
        }
        $this->_where = $returnArr;
        return $this;
    }

    public function orWhere($orWhere)
    {
        $paramsArray = [];
        foreach ($orWhere as $key => $value) {
            $paramsArray[] = $key . ':' . $value;
        }
        $this->_orWhere[] = implode(',', $paramsArray);
        return $this;
    }

    public function group($group)
    {
        $this->_group = $group;
        return $this;
    }

    public function order($order)
    {
        $this->_order = $order;
        return $this;
    }

    public function limit($limit)
    {
        $this->_limit = $limit;
        return $this;
    }

    public function page($page)
    {
        $this->_page = $page;
        return $this;
    }

    public function search($searchText)
    {
        if ($searchText == '') {
            $this->_search = $searchText;
        } else {
            $this->_search = $this->urlsafeB64Encode($searchText);
        }
        return $this;
    }

    public function toURL($responseType = null)
    {
        $model = str_replace('-', '.', $this->className);
        $url = implode('/', [$this->base, $this->controller, $this->version, $model]);

        $params = [];
        if (!is_null($this->_action)) {
            $params[] = '_action=' . $this->_action;
        }

        if (count($this->_fields) > 0) {
            $params[] = '_fields=' . implode(',', $this->_fields);
        }

        if (is_array($this->_contain)) {
            if (count($this->_contain) > 0) {
                $params[] = '_contain=' . implode(',', $this->_contain);
            }
        }

        if (is_array($this->_innerJoinWith)) {
            if (count($this->_innerJoinWith) > 0) {
                $params[] = '_innerJoinWith=' . implode(',', $this->_innerJoinWith);
            }
        }

        if (count($this->_finder) > 0) {
            $params[] = '_finder=' . implode(',', $this->_finder);
        }
        if (count($this->_group) > 0) {
            $params[] = '_group=' . implode(',', $this->_group);
        }
        if (count($this->_order) > 0) {
            $params[] = '_order=' . implode(',', $this->_order);
        }
        if (count($this->_where) > 0) {
            foreach ($this->_where as $key => $value) {
                $params[] = $key . '=' . $value;
            }
        }
        if (count($this->_orWhere) > 0) {
            $params[] = '_orWhere=' . implode(',', $this->_orWhere);
        }
        $params[] = '_limit=' . $this->_limit;
        if ($this->_page > 0) {
            $params[] = '_page=' . $this->_page;
        }

        if ($this->_db != 'default') {
            $params[] = '_db=' . $this->_db;
        }

        if (is_null($this->_showBlobContent)) {
            $params[] = '_showBlobContent='.$this->_showBlobContent;
        }

        if ($this->_schema) {
            $params[] = '_schema='.$this->_schema;
        }

        if ($this->_querystring) {
            $params[] = '_querystring='.$this->_querystring;
        }

        if ($this->_search) {
            $params[] = '_search='.$this->_search;
        }

        if ($this->id > 0 || (is_string($this->id) && $this->id == 'schema')) {
            $url .= '/' . $this->id;
        }
        if (!empty($responseType)) {
            $this->responseType = $responseType;
        }
        $url .= '.' . $this->responseType;

        if (count($params) > 0) {
            $url .= '?' . implode('&', $params);
        }

        return $url;
    }

    public function save($data)
    {
        $url = $this->toURL();
        $headerOption =  !empty($this->getHeaders()) ? ['headers' => $this->getHeaders()] : [];
        $http = new Client();
        $response = $http->post($url, $data, $headerOption);
        $this->reset();
        return $this->extractData($response);
    }

    public function edit($data)
    {
        $headerOption =  !empty($this->getHeaders()) ? ['headers' => $this->getHeaders()] : [];
        $model = str_replace('.', '-', $this->className);
        $url = $this->toURL();
        $http = new Client();
        $response = $http->patch($url, $data, $headerOption);
        $this->reset();
        return $this->extractData($response);
    }

    public function delete($data)
    {
        $headerOption =  !empty($this->getHeaders()) ? ['headers' => $this->getHeaders()] : [];
        $model = str_replace('.', '-', $this->className);
        $url = $this->toURL();
        $http = new Client();
        $response = $http->delete($url, $data, $headerOption);
        $this->reset();
        return $this->extractData($response);
    }

    public function ajax()
    {
        $headerOption =  !empty($this->getHeaders()) ? ['headers' => $this->getHeaders()] : [];
        $url = $this->toURL();
        $http = new Client();
        $response = $http->get($url, [], $headerOption);
        $this->reset();
        return $this->extractData($response);
    }

    private function extractData($response)
    {
        switch ($this->responseType) {
            case 'json':
                return json_decode($response->body(), true);
                break;

            case 'xml':
                $xml = simplexml_load_string($response->body());
                $json = json_encode($xml);
                return json_decode($json, true);
                break;

            default:
                return [];
        }
    }
}
