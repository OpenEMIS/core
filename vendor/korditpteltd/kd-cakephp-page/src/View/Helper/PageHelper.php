<?php
namespace Page\View\Helper;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\I18n\Date;
use Cake\I18n\Time;
use Cake\I18n\I18n;
use Cake\Log\Log;
use Cake\Utility\Hash;
use Cake\Utility\Text;
use Cake\View\Helper;
use Cake\Core\Configure;

use Page\Traits\RTLTrait;
use Page\Traits\EncodingTrait;

class PageHelper extends Helper
{
    use EncodingTrait;
    use RTLTrait;

    public $helpers = ['Form', 'Html', 'Paginator', 'Url'];

    private $cakephpReservedPassKeys = [
        'controller',
        'action',
        'plugin',
        'pass',
        '_matchedRoute',
        '_Token',
        '_csrfToken',
        'paging'
    ];

    public $includes = [
        'datepicker' => [
            'include' => false,
            'css' => 'Page.../plugins/datepicker/css/bootstrap-datepicker.min',
            'js' => 'Page.../plugins/datepicker/js/bootstrap-datepicker.min',
            'element' => 'Page.datepicker_js'
        ],
        'timepicker' => [
            'include' => false,
            'css' => 'Page.../plugins/timepicker/css/bootstrap-timepicker.min',
            'js' => 'Page.../plugins/timepicker/js/bootstrap-timepicker.min',
            'element' => 'Page.timepicker_js'
        ],
        'chosen' => [
            'include' => false,
            'css' => 'Page.../plugins/chosen/css/chosen.min',
            'js' => 'Page.../plugins/chosen/js/chosen.jquery.min'
        ],
        'jasny' => [
            'include' => false,
            'css' => 'Page.../plugins/jasny/css/jasny-bootstrap.min',
            'js' => 'Page.../plugins/jasny/js/jasny-bootstrap.min'
        ]
    ];

    public function includes()
    {
        $includes = new ArrayObject($this->includes);

        foreach ($includes as $include) {
            if ($include['include'] == false) {
                continue;
            }

            if (array_key_exists('css', $include)) {
                if (is_array($include['css'])) {
                    foreach ($include['css'] as $css) {
                        echo $this->Html->css($css, ['block' => true]);
                    }
                } else {
                    echo $this->Html->css($include['css'], ['block' => true]);
                }
            }
            if (array_key_exists('js', $include)) {
                if (is_array($include['js'])) {
                    foreach ($include['js'] as $js) {
                        echo $this->Html->script($js, ['block' => true]);
                    }
                } else {
                    echo $this->Html->script($include['js'], ['block' => true]);
                }
            }
            if (array_key_exists('element', $include)) {
                $this->_View->element($include['element']);
            }
        }
    }

    public function getFormTemplate()
    {
        return [
            'select' => '<div class="input-select-wrapper"><select name="{{name}}" {{attrs}}>{{content}}</select></div>',
            'radio'  => '<input type="radio" class = "iradio_minimal-grey icheck-input" name="{{name}}" value="{{value}}"{{attrs}}>'
        ];
    }

    public function getFormOptions()
    {
        $options = [
            'id' => 'content-main-form',
            'class' => 'form-horizontal',
            'novalidate' => true,
            'onSubmit' => '$(\'button[type="submit"]\').click(function() { return false; });'
        ];

        $elements = $this->_View->get('elements');
        if (!empty($elements)) {
            $types = ['binary', 'image'];
            foreach ($elements as $key => $attr) {
                if (in_array($attr['controlType'], $types)) {
                    $options['type'] = 'file';
                    break;
                }
            }
        }

        return $options;
    }

    public function getFormButtons()
    {
        $buttons = new ArrayObject([]);

        // save button
        $buttons[] = [
            'name' => '<i class="fa fa-check"></i> ' . __('Save'),
            'attr' => ['class' => 'btn btn-default btn-save', 'div' => false, 'name' => 'submit', 'value' => 'save']
        ];

        // cancel button
        $backBtn = null;//$this->_View->get('backButton');
        // $buttons[] = [
        //     'name' => '<i class="fa fa-close"></i> ' . __('Cancel'),
        //     'attr' => [
        //         'class' => 'btn btn-outline btn-cancel',
        //         'onclick' => 'console.log("asd"); return false',
        //         'escape' => false
        //     ],
        //     'url' => !is_null($backBtn) ? $backBtn['url'] : []
        // ];

        // $config = $this->_View->get('ControllerAction');
        // $table = $config['table'];

        // attach event for updating form buttons
        // $eventKey = 'ControllerAction.Model.onGetFormButtons';
        // $event = $this->dispatchEvent($table, $eventKey, null, [$buttons]);
        // end attach event

        $html = '';
        if ($buttons->count() > 0) {
            $html = '<div class="form-buttons"><div class="button-label"></div>';
            foreach ($buttons as $btn) {
                if (!array_key_exists('url', $btn)) {
                    $html .= $this->Form->button($btn['name'], $btn['attr']);
                } else {
                    $html .= $this->Html->link($btn['name'], $btn['url'], $btn['attr']);
                }
            }
            $html .= $this->_View->element('Page.cancel');
            // $html .= $this->Form->button('reload', ['id' => 'reload', 'type' => 'submit', 'name' => 'submit', 'value' => 'reload', 'class' => 'hidden']);
            $html .= '</div>';
        }
        return $html;
    }

    public function getPaginatorButtons($type = 'prev')
    {
        $icon = array('prev' => '', 'next' => '');
        $html = $this->Paginator->{$type}(
            $icon[$type],
            array('tag' => 'li', 'escape' => false, 'url' => $this->getUrl(['action' => $this->request->param('action')], ['toArray' => true])),
            null,
            array('tag' => 'li', 'class' => 'disabled', 'disabledTag' => 'a', 'escape' => false, 'url' => $this->getUrl(['action' => $this->request->param('action')], ['toArray' => true]))
        );
        return $html;
    }

    public function getPaginatorNumbers()
    {
        $html = $this->Paginator->numbers(array(
            'tag' => 'li',
            'currentTag' => 'a',
            'currentClass' => 'active',
            'separator' => '',
            'modulus' => 4,
            'first' => 2,
            'last' => 2,
            'ellipsis' => '<li><a>...</a></li>',
            'url' => $this->getUrl(['action' => $this->request->param('action')], ['toArray' => true])
        ));
        return $html;
    }

    public function locale($locale = null)
    {
        if (!empty($locale)) {
            return I18n::locale($locale);
        } else {
            return I18n::locale();
        }
    }

    public function getTableHeaders()
    {
        $headers = [];
        $elements = $this->_View->get('elements');

        foreach ($elements as $field => $attr) {
            $label = $attr['label'];

            if ($attr['sortable']) {
                $url = $this->getUrl(['action' => $this->request->param('action')], ['toArray' => true]);
                if (array_key_exists('sort', $url)) {
                    unset($url['sort']);
                }
                if (array_key_exists('direction', $url)) {
                    unset($url['direction']);
                }
                $label = $this->Paginator->sort($field, $label, ['url' => $url]);
            }

            $headers[] = $label;
        }

        $disabledActions = $this->_View->get('disabledActions');
        $actionButtons = ['view', 'edit', 'delete'];
        if (count(array_intersect($actionButtons, $disabledActions)) < count($actionButtons)) {
            $headers[] = [__('Actions') => ['class' => 'cell-action']];
        }
        if (!in_array('reorder', $disabledActions)) {
            $headers[] = [__('Reorder') => ['class' => 'cell-reorder']];
        }

        return $headers;
    }

    public function getTableData()
    {
        $tableData = [];
        $data = $this->_View->get('data');
        $fields = $this->_View->get('elements');

        foreach ($data as $entity) {

        // POCOR-3519
	    if (!empty($entity->message)) { 
	              $entity->message = h($entity->message, ENT_QUOTES);
	    }


            $row = [];
            foreach ($fields as $field => $attr) {
                if (($attr['controlType'] == 'string' || $attr['controlType'] == 'text') && !$this->isRTL($this->getValue($entity, $attr))) {
                    $row[] = '<div style = "direction: ltr !important">' . $this->getValue($entity, $attr) . '</div>';
                } else {
                    $row[] = $this->getValue($entity, $attr);
                }
            }
            $disabledActions = $this->_View->get('disabledActions');
            $actionButtons = ['view', 'edit', 'delete'];
            if (count(array_intersect($actionButtons, $disabledActions)) < count($actionButtons)) {
                $row[] = $this->_View->element('Page.actions', ['data' => $entity]);
            }
            if (!in_array('reorder', $disabledActions)) {
                $model = TableRegistry::get($entity->source());
                $primaryKeys = $model->primaryKey();

                $primaryKeyValue = [];
                if (is_array($primaryKeys)) {
                    foreach ($primaryKeys as $key) {
                        $primaryKeyValue[$key] = $entity->getOriginal($key);
                    }
                } else {
                    $primaryKeyValue[$primaryKeys] = $entity->getOriginal($primaryKeys);
                }

                $encodedKeys = $this->encode($primaryKeyValue);
                $row[] = [$this->_View->element('Page.reorder'), ['class' => 'sorter', 'data-row-id' => $encodedKeys]];
            }

            $tableData[] = $row;
        }
        return $tableData;
    }

    public function highlight($value)
    {
        $search = $this->getQueryString('search');
        if ($search !== false) {
            $value = Text::highlight($value, $search);
        }
        return $value;
    }

    public function getLimitOptions()
    {
        $paging = $this->_View->get('paging');
        $limitOptions = $paging['limitOptions'];

        $html = '';
        $limit = $this->getQueryString('limit') !== false ? $this->getQueryString('limit') : '';
        if (!empty($limitOptions)) {
            $cakephpVersion = Configure::version();
            if (version_compare($cakephpVersion, '3.4.0', '>=')) {
                $html .= $this->Form->control('Search.limit', [
                    'label' => false,
                    'options' => $limitOptions,
                    'value' => $limit,
                    'templates' => ['select' => '<div class="input-select-wrapper"><select name="{{name}}" {{attrs}}>{{content}}</select></div>'],
                    'onchange' => "Page.querystring('limit', this.value, this)"
                ]);
            } else {
                $html .= $this->Form->input('Search.limit', [
                    'label' => false,
                    'options' => $limitOptions,
                    'value' => $limit,
                    'templates' => ['select' => '<div class="input-select-wrapper"><select name="{{name}}" {{attrs}}>{{content}}</select></div>'],
                    'onchange' => "Page.querystring('limit', this.value, this)"
                ]);
            }
        }
        return $html;
    }

    public function getQueryString($key)
    {
        $querystring = $this->request->query('querystring');
        $value = false;
        if ($querystring) {
            $object = $this->decode($querystring);
            $value = array_key_exists($key, $object) ? $object[$key] : '';
        }
        return $value;
    }

    public function getUrl($url, $options = [])
    {
        $request = $this->request;
        $toArray = isset($options['toArray']) ? $options['toArray'] : false;
        $urlParams = isset($options['urlParams']) ? $options['urlParams'] : true; /* 'PASS' | 'QUERY' | false */

        $this->mergeRequestParams($url);

        if ($urlParams === true) {
            $url = array_merge($url, $request->pass, $request->query);
        } elseif ($urlParams === 'PASS') {
            $url = array_merge($url, $request->pass);
        } elseif ($urlParams === 'QUERY') {
            $url = array_merge($url, $request->query);
        }
        return $toArray ? $url : $this->Url->build($url);
    }

    private function mergeRequestParams(array &$url)
    {
        $requestParams = $this->request->params;
        foreach ($requestParams as $key => $value) {
            if (is_numeric($key) || in_array($key, $this->cakephpReservedPassKeys)) {
                unset($requestParams[$key]);
            }
        }
        $url = array_merge($url, $requestParams);
    }

    private function getValue($entity, $field)
    {
        $controlType = $field['controlType'];

        $array = $entity instanceof Entity ? $entity->toArray() : $entity;
        $data = Hash::flatten($array);
        $value = array_key_exists($field['key'], $data) ? $data[$field['key']] : '';
        if (array_key_exists('displayFrom', $field)) { // if displayFrom exists, always get value based on displayFrom
            $key = $field['displayFrom'];
            if (array_key_exists($key, $data)) {
                $value = $data[$key];
            }
        } else {
            $isDropdownType = $controlType == 'dropdown';
            $isOptionsExists = array_key_exists('options', $field);
            if ($isDropdownType && $isOptionsExists) {
                $options = $field['options'];
                $valueExistsInOptions = array_key_exists($value, $options);
                if ($valueExistsInOptions) {
                    $value = $options[$value];
                }
            }
        }

        $isDateTimeType = in_array($controlType, ['date', 'time']);
        $isStringType = in_array($controlType, ['string', 'textarea']);
        $hasDateTimeFormat = array_key_exists('format', $field);
        $valueIsNotEmpty = !empty($value);
        $action = !is_null($this->request->param('action')) ? $this->request->param('action') : 'index';

        if ($isDateTimeType && $hasDateTimeFormat && $valueIsNotEmpty) {
            $valueIsDateObject = $value instanceof Date;
            if ($valueIsDateObject) {
                $value = $value->i18nFormat($field['format']);
            } else {
                $value = (new Date($value))->i18nFormat($field['format']);
            }
        } elseif (($action == 'index') && ($isStringType || $field['foreignKey'] != false) && $valueIsNotEmpty) {
            $value = $this->highlight($value);
        }
        return $value;
    }

    public function renderInputElements()
    {
        $html = '';
        $fields = $this->_View->get('elements');
        $data = $this->_View->get('data');

        if (!empty($fields)) {
            foreach ($fields as $field) {
                $controlType = $field['controlType'];
                if (method_exists($this, $controlType)) {
                    $html .= $this->$controlType($field, $data);
                } else {
                    Log::write('error', 'Missing control type implementation: ' . $controlType);
                }
            }
        } else {
            pr('There are no elements');
        }

        return $html;
    }

    public function renderViewElements($fields)
    {
        $html = '';

        $row = <<<EOT
<div class="row">
    <div class="col-xs-6 col-md-3 form-label">%s</div>
    <div class="form-input">%s</div>
</div>
EOT;

        $excludedTypes = ['hidden'];

        foreach ($fields as $field => $attr) {
            $controlType = $attr['controlType'];
            $isVisible = $attr['visible'];
            $value = '';

            if (!$isVisible || $controlType == 'hidden') {
                continue;
            }

            $label = $attr['label'];
            if (is_array($label)) {
                $label = $label['text'];
            }

            if (array_key_exists('value', $attr['attributes'])) {
                $value = $attr['attributes']['value'];
                if (($attr['controlType'] == 'string' || $attr['controlType'] == 'text') && !$this->isRTL($value)) {
                    $value = '<div style = "direction:ltr !important">' . $value . '</div>';
                }
            }

            switch ($controlType) {
                case 'section':
                case 'table':
                case 'binary':
                    $html .= $this->{$controlType}($attr, null, 'view');
                    break;

                case 'link':
                    if (array_key_exists('href', $attr['attributes'])) {
                        $value = $this->Html->link($value, $attr['attributes']['href']);
                    }
                    // fall through
                case 'textarea':
                    $value = h($value, ENT_QUOTES);// POCOR-3519
                    $value = nl2br($value);
                    // fall through
                default:
                    $html .= sprintf($row, $label, $value);
                    break;
            }
        }
        return $html;
    }

    private function extractHtmlAttributes(array &$field, $data)
    {
        $key = $field['key'];
        $options = $field['attributes'];
        if (array_key_exists('name', $options)) {
            unset($options['name']);
        }

        if (array_key_exists('label', $field)) {
            $options['label'] = $field['label'];
        }

        if (array_key_exists('options', $field)) {
            $options['options'] = $field['options'];
        }

        $invalidFields = $data->invalid();
        if (array_key_exists($key, $invalidFields)) {
            $value = $invalidFields[$key];
            if (is_array($value) && array_key_exists('_ids', $value)) { // for multi select
                $value = $invalidFields[$key]['_ids'];
            }
            $options['value'] = $value;
            $field['attributes']['value'] = $value;
        }
        return $options;
    }

    private function binary(array $field, $data, $action = 'edit')
    {
        if ($action == 'view') {
            $html = '';
            $row = <<<EOT
<div class="row">
    <div class="col-xs-6 col-md-3 form-label">%s</div>
    <div class="form-input">%s</div>
</div>
EOT;
            if (isset($field['attributes']['value']) && $field['attributes']['value']) {
                if (isset($field['attributes']['type']) && $field['attributes']['type'] == 'image') {
                    $link = '<div class="table-thumb"><img src="%s" style="max-width:60px;background-color:%s;"></div>';
                    $backgroundColour = isset($field['attributes']['backgroundColor']) ? $field['attributes']['backgroundColor'] : '#FFFFFF';
                    $link = sprintf($link, $field['attributes']['value'], $backgroundColour);
                } else {
                    $link = $this->Html->link($field['attributes']['file_name'], $field['attributes']['value']);
                }
            } else {
                $link = '';
            }

            $label = $field['label'];
            $html = sprintf($row, $label, $link);
            return $html;
        } else {
            $type = isset($field['attributes']['type']) ? $field['attributes']['type'] : 'file';

            if ($type == 'file') {
                $options = ['type' => 'file', 'class' => 'form-control', 'label' => false];
                $required = $field['attributes']['required'];
                $fileNameColumn = isset($field['attributes']['fileNameColumn']) ? $field['attributes']['fileNameColumn'] : 'file_name';
                $fileSizeLimit = isset($field['attributes']['fileSizeLimit']) ? $field['attributes']['fileSizeLimit'] : 1;
                $formatSupported = isset($field['attributes']['supportedFileFormat']) ? $field['attributes']['supportedFileFormat'] : ['jpeg', 'jpg', 'gif', 'png', 'rtf', 'txt', 'csv', 'pdf', 'ppt', 'pptx', 'doc', 'docx', 'xls', 'xlsx', 'zip', 'odt', 'ods', 'key', 'pages', 'numbers'];
                $fileContent = '';
                if (is_resource($data[$field['key']])) {
                    $streamedContent = stream_get_contents($data[$field['key']]);
                    $fileContent = base64_encode($streamedContent);
                    $fileContentSize = strlen($streamedContent);
                } else {
                    $fileContent = isset($data[$field['key'].'_content']) ? $data[$field['key'].'_content'] : null;
                    $fileContentSize = isset($data[$field['key'].'_file_size']) ? $data[$field['key'].'_file_size'] : null;
                }

                $comments = '';
                $fileSizeMessage = '* '.str_replace('%s', $fileSizeLimit.'MB', __('File size should not be larger than %s.'));
                $extensionSupported = '';
                $fileFormatMessage = '* '. sprintf(__('Format Supported: %s'), implode(', ', $formatSupported));
                foreach ($formatSupported as &$format) {
                    $format = '\''.$format.'\'';
                }
                $extensionSupported = implode(', ', $formatSupported);
                $comments .= $fileSizeMessage . '<br/>' . $fileFormatMessage;
                $fileName = '';
                if ($data instanceof Entity) {
                    $fileName = $data->offsetExists($fileNameColumn) ? $data->$fileNameColumn : null;
                } elseif (is_array($data)) {
                    $fileName = isset($data[$fileNameColumn]) ? $data[$fileNameColumn] : null;
                }

                if ($required) {
                    $options['required'] = 'required';
                }

                $alias = explode('.', $field['attributes']['name'])[0];

                $attr = [
                    'id' => str_replace('.', '_', $field['attributes']['name']),
                    'key' => $field['key'],
                    'alias' => $alias,
                    'name' => $field['attributes']['name'],
                    'label' => $field['label'],
                    'options' => $options,
                    'required' => $required ? ' required' : '',
                    'comments' => $comments ? $comments : '',
                    'fileNameColumn' => $fileNameColumn,
                    'fileName' => $fileName,
                    'fileSizeLimit' => $fileSizeLimit,
                    'fileContent' => $fileContent,
                    'fileContentSize' => $fileContentSize,
                    'extensionSupported' => $extensionSupported
                ];
                $this->includes['jasny']['include'] = true;
                return $this->_View->element('Page.file_upload', $attr);
            } elseif ($type == 'image') {
                // Image
                $message = isset($field['attributes']['imageMessage']) ? $field['attributes']['imageMessage'] : '';
                $fileSizeLimit = isset($field['attributes']['fileSizeLimit']) ? $field['attributes']['fileSizeLimit'] : 1;
                $formatSupported = isset($field['attributes']['supportedFileFormat']) ? $field['attributes']['supportedFileFormat'] : ['jpeg', 'jpg', 'gif', 'png', 'rtf', 'txt', 'csv', 'pdf', 'ppt', 'pptx', 'doc', 'docx', 'xls', 'xlsx', 'zip', 'odt', 'ods', 'key', 'pages', 'numbers'];
                $defaultImgViewClass = "logo-image";
                $defaultImgView = '<div class=\"profile-image\"><i class=\"kd-openemis fa-3x\"></i></div>';
                $defaultWidth = 90;
                $defaultHeight = 115;
                $disabled = $field['attributes']['disabled'];
                $backgroundColor = isset($field['attributes']['backgroundColor']) ? $field['attributes']['backgroundColor'] :'#FFFFFF';

                $comments = !empty($message) ? $message . '<br/>' : $message;
                $fileSizeMessage = '* ' . str_replace('%s', $fileSizeLimit, __('File size should not be larger than %s.'));
                $extensionSupported = '';
                $fileFormatMessage = '* ' . sprintf(__('* Format Supported: %s'), implode(', ', $formatSupported));
                foreach ($formatSupported as &$format) {
                    $format = '\''.$format.'\'';
                }
                $extensionSupported = implode(', ', $formatSupported);
                $comments .= $fileSizeMessage . '<br/>' . $fileFormatMessage;
                $defaultImgMsg = '<p>'. $comments .'</p>';
                $showRemoveButton = false;
                if (isset($data[$field['key']]['tmp_name'])) {
                    $tmp_file = ((is_array($data[$field['key']])) && (file_exists($data[$field['key']]['tmp_name']))) ? $data[$field['key']]['tmp_name'] : "";
                    $tmp_file_read = (!empty($tmp_file)) ? file_get_contents($tmp_file) : "";
                } else {
                    $tmp_file = true;
                    $tmp_file_read = $data[$field['key']];
                }

                if (isset($field['attributes']['defaultHeight'])) {
                    $defaultWidth = $field['attributes']['defaultWidth'];
                }
                if (isset($field['attributes']['defaultHeight'])) {
                    $defaultWidth = $field['attributes']['defaultHeight'];
                }

                if (!is_resource($tmp_file_read)) {
                    $src = (!empty($tmp_file_read)) ? '<img id="existingImage'.$field['key'].'" style="max-width: ' . $defaultWidth . 'px; max-height: ' . $defaultHeight . 'px;background-color: '.$backgroundColor.'" class="'.$defaultImgViewClass.'" src="data:image/jpeg;base64,'.base64_encode($tmp_file_read).'"/>' : str_replace('\\', '', $defaultImgView);
                    $showRemoveButton = (!empty($tmp_file)) ? true : false;
                } else {
                    $src = (!empty($tmp_file_read)) ? '<img id="existingImage'.$field['key'].'" style="max-width: ' . $defaultWidth . 'px; max-height: ' . $defaultHeight . 'px;background-color: '.$backgroundColor.'" class="'.$defaultImgViewClass.'" src="'.$field['attributes']['value']['src'].'"/>' : str_replace('\\', '', $defaultImgView);
                    $showRemoveButton = true;
                }

                $this->includes['jasny']['include'] = true;
                return $this->_View->element('Page.image_uploader', [
                    'attr' => $field,
                    'disabled' => $disabled,
                    'src' => $src,
                    'defaultWidth' => $defaultWidth,
                    'defaultHeight' => $defaultHeight,
                    'showRemoveButton' => $showRemoveButton,
                    'defaultImgMsg' => $defaultImgMsg,
                    'defaultImgView' => $defaultImgView
                ]);
            }
        }
    }

    private function string(array $field, $data)
    {
        $options = $this->extractHtmlAttributes($field, $data);
        $options['type'] = 'string';
        $html = '';
        $cakephpVersion = Configure::version();

        if (array_key_exists('disabled', $options) && array_key_exists('displayFrom', $field)) {
            $options['type'] = 'hidden';
            unset($options['disabled']);
            $value = $this->getValue($data, $field);
            if (version_compare($cakephpVersion, '3.4.0', '>=')) {
                $html .= $this->Form->control($field['key'].'_name', ['value' => $value, 'disabled' => 'disabled', 'label' => $field['label']]);
            } else {
                $html .= $this->Form->input($field['key'].'_name', ['value' => $value, 'disabled' => 'disabled', 'label' => $field['label']]);
            }
        }

        if (version_compare($cakephpVersion, '3.4.0', '>=')) {
            $html .= $this->Form->control($field['attributes']['name'], $options);
        } else {
            $html .= $this->Form->input($field['attributes']['name'], $options);
        }
        return $html;
    }

    private function password(array $field, $data)
    {
        $options = $this->extractHtmlAttributes($field, $data);
        $options['type'] = 'password';
        $html = '';
        $cakephpVersion = Configure::version();

        if (version_compare($cakephpVersion, '3.4.0', '>=')) {
            $html .= $this->Form->control($field['attributes']['name'], $options);
        } else {
            $html .= $this->Form->input($field['attributes']['name'], $options);
        }
        return $html;
    }

    private function integer(array $field, $data)
    {
        $options = $this->extractHtmlAttributes($field, $data);
        $options['type'] = 'number';
        $html = '';
        $cakephpVersion = Configure::version();

        if (array_key_exists('disabled', $options) && array_key_exists('displayFrom', $field)) {
            $options['type'] = 'hidden';
            unset($options['disabled']);
            $value = $this->getValue($data, $field);
            if (version_compare($cakephpVersion, '3.4.0', '>=')) {
                $html .= $this->Form->control($field['key'].'_name', ['value' => $value, 'disabled' => 'disabled', 'label' => $field['label']]);
            } else {
                $html .= $this->Form->input($field['key'].'_name', ['value' => $value, 'disabled' => 'disabled', 'label' => $field['label']]);
            }
        }

        if (version_compare($cakephpVersion, '3.4.0', '>=')) {
            $html .= $this->Form->control($field['attributes']['name'], $options);
        } else {
            $html .= $this->Form->input($field['attributes']['name'], $options);
        }
        return $html;
    }

    private function section(array $field, $data, $action = 'edit')
    {
        return $this->Html->div('section-header', $field['label']);
    }

    private function float(array $field, $data)
    {
        return $this->integer($field, $data);
    }

    private function decimal(array $field, $data)
    {
        return $this->integer($field, $data);
    }

    private function textarea(array $field, $data)
    {
        $options = $this->extractHtmlAttributes($field, $data);
        $options['type'] = 'textarea';
        $cakephpVersion = Configure::version();
        if (version_compare($cakephpVersion, '3.4.0', '>=')) {
            return $this->Form->control($field['attributes']['name'], $options);
        } else {
            return $this->Form->input($field['attributes']['name'], $options);
        }
    }

    private function dropdown(array $field, $data)
    {
        // Log::write('debug', 'Deprecated')
        return $this->select($field, $data);
    }

    private function select(array $field, $data)
    {
        $options = $this->extractHtmlAttributes($field, $data);
        $options['type'] = 'select';

        if (array_key_exists('dependentOn', $field) && array_key_exists('params', $field)) {
            $options['dependent-on'] = $field['dependentOn'];
            $options['params'] = $field['params'];
        }

        if (array_key_exists('multiple', $options)) {
            return $this->multiselect($field, $data);
        }

        $cakephpVersion = Configure::version();
        if (version_compare($cakephpVersion, '3.4.0', '>=')) {
            return $this->Form->control($field['attributes']['name'], $options);
        } else {
            return $this->Form->input($field['attributes']['name'], $options);
        }
    }

    private function multiselect(array $field, $data)
    {
        $options = $this->extractHtmlAttributes($field, $data);
        $options['type'] = 'select';
        $options['multiple'] = 'multiple';
        
        $Locales = TableRegistry::get('Locales');
        $langDir = $Locales->getLangDir(I18n::locale());
        $options['class'] = ($langDir == 'rtl') ? 'chosen-select chosen-rtl' : 'chosen-select';

        $options['data-placeholder'] = '';
        if (array_key_exists('placeholder', $options)) {
            $options['data-placeholder'] = $options['placeholder'];
            unset($options['placeholder']);
        }

        if (empty($options['options'])) {
            $options['data-placeholder'] = __('No Options');
        }
        $this->includes['chosen']['include'] = true;

        $cakephpVersion = Configure::version();
        if (version_compare($cakephpVersion, '3.4.0', '>=')) {
            return $this->Form->control($field['attributes']['name'], $options);
        } else {
            return $this->Form->input($field['attributes']['name'], $options);
        }
    }

    private function hidden(array $field, $data)
    {
        $options = $this->extractHtmlAttributes($field, $data);
        return $this->Form->hidden($field['attributes']['name'], $options);
    }

    private function date(array $field, $data)
    {
        $key = $field['key'];
        $options = ['type' => 'text', 'class' => 'form-control', 'label' => false, 'error' => false];
        $required = isset($field['attributes']['required']) ? $field['attributes']['required'] : false;
        $disabled = isset($field['attributes']['disabled']) ? $field['attributes']['disabled'] : false;
        $value = isset($field['attributes']['value']) ? $field['attributes']['value'] : '';
        if ($data->invalid($key)) {
            $value = $data->invalid($key);
        }
        $dateOptions = [];

        if ($required) {
            $options['required'] = 'required';
        }

        if ($disabled) {
            $options['disabled'] = 'disabled';
        }

        $dateProperties = ['minDate' => 'startDate', 'maxDate' => 'endDate'];

        foreach ($dateProperties as $prop => $mapped) {
            if (array_key_exists($prop, $field['attributes'])) {
                $propValue = $field['attributes'][$prop];
                $dateOptions[$mapped] = implode('-', [$propValue['day'], $propValue['month'], $propValue['year']]);
            }
        }

        if (!empty($value)) {
            if ($value instanceof Date) {
                $options['value'] = $value->i18nFormat('dd-MM-yyyy');
            } else {
                $options['value'] = (new Date($value))->i18nFormat('dd-MM-yyyy');
            }
        } else {
            if ($required) {
                $options['value'] = (new Date())->i18nFormat('dd-MM-yyyy');
            }
        }

        $attr = [
            'id' => str_replace('.', '_', $field['attributes']['name']),
            'name' => $field['attributes']['name'],
            'label' => $field['label'],
            'options' => $options,
            'date_options' => $dateOptions,
            'required' => $required ? ' required' : ''
        ];

        // datepicker variable is used for initialising javascript in datepicker.ctp
        if (!$disabled) {
            if (!is_null($this->_View->get('datepicker'))) {
                $datepickers = $this->_View->get('datepicker');
                $datepickers[] = $attr;
                $this->_View->set('datepicker', $datepickers);
            } else {
                $this->_View->set('datepicker', [$attr]);
            }
        }

        $this->includes['datepicker']['include'] = true;

        return $this->_View->element('Page.date', $attr);
    }

    public function time(array $field, $data)
    {
        $value = '';

        $options = ['type' => 'text', 'class' => 'form-control', 'label' => false, 'error' => false];

        $_options = [
            'defaultTime' => false
        ];

        $required = isset($field['attributes']['required']) ? $field['attributes']['required'] : false;
        $disabled = isset($field['attributes']['disabled']) ? $field['attributes']['disabled'] : false;

        if (!isset($field['time_options'])) {
            $options['time_options'] = [];
        }
        if (!isset($field['default_time'])) {
            $options['default_time'] = true;
        }

        if ($disabled) {
            $options['disabled'] = 'disabled';
        }

        if (!isset($field['id'])) {
            $field['id'] = $field['attributes']['name'];
        }

        $options['time_options'] = array_merge($_options, $options['time_options']);

        if (($data instanceof Entity && $data->offsetExists($field['key'])) && $data[$field['key']] instanceof Time || (is_array($data) && isset($data[$field['key']])) && $data[$field['key']] instanceof Time) {
            $options['value'] = $data[$field['key']]->i18nFormat('h:mm a');
            $options['time_options']['defaultTime'] = $options['value'];
        } else {
            $options['value'] = date('h:i A', strtotime($data[$field['key']]));
            $options['time_options']['defaultTime'] = $options['value'];
        }

        $attr = [
            'id' => str_replace('.', '_', $field['attributes']['name']),
            'name' => $field['attributes']['name'],
            'label' => $field['label'],
            'required' => $required ? ' required' : '',
            'options' => $options
        ];

        $options['id'] = $attr['id'];
        $options['name'] = $attr['name'];

        if (!is_null($this->_View->get('timepicker'))) {
            $timepickers = $this->_View->get('timepicker');
            $timepickers[] = $options;
            $this->_View->set('timepicker', $timepickers);
        } else {
            $this->_View->set('timepicker', [$options]);
        }
        $this->includes['timepicker']['include'] = true;
        $value = $this->_View->element('Page.time', $attr);

        return $value;
    }

    public function table(array $field, $data, $action = 'edit')
    {
        $html = '';
        if ($action == 'view') {
            $html = '
                <div class="row">
                    <div class="col-xs-6 col-md-3 form-label">%s</div>
                    <div class="table-wrapper">
                        <div class="table-in-view">
                            <table class="table">
                                <thead>%s</thead>
                                <tbody>%s</tbody>
                            </table>
                        </div>
                    </div>
                </div>';
        } else {
            $html = '
                <div class="input clearfix">
                    <label>%s</label>
                    <div class="table-wrapper">
                        <div class="table-in-view">
                            <table class="table">
                                <thead>%s</thead>
                                <tbody>%s</tbody>
                            </table>
                        </div>
                    </div>
                </div>
            ';
        }
        $header = array_column($field['attributes']['column'], 'label');
        $headers = $this->Html->tableHeaders($header);

        $cells = [];
        foreach ($field['attributes']['row'] as $row) {
            $r = [];
            foreach ($row as $k => $v) {
                foreach (array_column($field['attributes']['column'], 'key') as $h) {
                    if ($h == $k) {
                        $r[] = $v;
                    }
                }
            }
            $cells[] = $r;
        }
        $cells = $this->Html->tableCells($cells);
        $html = sprintf($html, $field['label'], $headers, $cells);

        return $html;
    }

    public function afterRender()
    {
        $this->includes();
    }
}
