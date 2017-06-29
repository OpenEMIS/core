<?php
namespace Page\View\Helper;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\I18n\Date;
use Cake\Log\Log;
use Cake\View\Helper;

use Page\Traits\EncodingTrait;

class PageHelper extends Helper
{
    use EncodingTrait;

    public $helpers = ['Form', 'Html', 'Paginator', 'Url'];

    public $includes = [
        'datepicker' => [
            'include' => false,
            'css' => 'ControllerAction.../plugins/datepicker/css/bootstrap-datepicker.min',
            'js' => 'ControllerAction.../plugins/datepicker/js/bootstrap-datepicker.min',
            'element' => 'Page.datepicker_js'
        ],
        'timepicker' => [
            'include' => false,
            'css' => 'ControllerAction.../plugins/timepicker/css/bootstrap-timepicker.min',
            'js' => 'ControllerAction.../plugins/timepicker/js/bootstrap-timepicker.min',
            'element' => 'ControllerAction.bootstrap-timepicker/timepicker'
        ],
        'chosen' => [
            'include' => false,
            'css' => 'ControllerAction.../plugins/chosen/css/chosen.min',
            'js' => 'ControllerAction.../plugins/chosen/js/chosen.jquery.min'
        ],
        'jasny' => [
            'include' => false,
            'css' => 'ControllerAction.../plugins/jasny/css/jasny-bootstrap.min',
            'js' => 'ControllerAction.../plugins/jasny/js/jasny-bootstrap.min'
        ]
    ];

    public function includes()
    {
        $includes = new ArrayObject($this->includes);

        foreach ($includes as $include) {
            if ($include['include']) {
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
    }

    public function getFormTemplate()
    {
        return [
            'select' => '<div class="input-select-wrapper"><select name="{{name}}" {{attrs}}>{{content}}</select></div>',
            'radio'  => '<input type="radio" class = "iradio_minimal-grey icheck-input" name="{{name}}" value="{{value}}"{{attrs}}>'
        ];
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

    public function getTableHeaders()
    {
        $headers = [];
        $elements = $this->_View->get('elements');

        foreach ($elements as $field => $attr) {
            $label = $attr['label'];

            if ($attr['sortable']) {
                $label = $this->Paginator->sort($field, $label);
            }

            $headers[] = $label;
        }

        $headers[] = [__('Actions') => ['class' => 'cell-action']];
        return $headers;
    }

    public function getTableData()
    {
        $tableData = [];
        $data = $this->_View->get('data');
        $fields = $this->_View->get('elements');

        foreach ($data as $entity) {
            $row = [];
            foreach ($fields as $field => $attr) {
                $row[] = $this->getValue($entity, $attr);
            }
            $row[] = $this->_View->element('Page.actions', ['entity' => $entity]);

            $tableData[] = $row;
        }
        return $tableData;
    }

    public function getLimitOptions()
    {
        $paging = $this->_View->get('paging');
        $limitOptions = $paging['limitOptions'];

        $html = '';
        $limit = $this->getQueryString('limit') !== false ? $this->getQueryString('limit') : '';
        if (!empty($limitOptions)) {
            $html .= $this->Form->input('Search.limit', [
                'label' => false,
                'options' => $limitOptions,
                'value' => $limit,
                'templates' => ['select' => '<div class="input-select-wrapper"><select name="{{name}}" {{attrs}}>{{content}}</select></div>'],
                'onchange' => "Page.querystring('limit', this.value)"
            ]);
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

    public function getUrl($route, $toArray = false)
    {
        $request = $this->request;
        $url = array_merge($route, $request->query);
        return $toArray ? $url : $this->Url->build($url);
    }

    private function getValue(Entity $entity, $field)
    {
        $value = '';
        $controlType = $field['controlType'];

        if (array_key_exists('displayFrom', $field)) {
            $displayFrom = explode('.', $field['displayFrom']);
            $value = $entity;
            foreach ($displayFrom as $key) {
                if ($value->has($key)) {
                    $value = $value->$key;
                } else {
                    break;
                }
            }
        } else {
            $value = $entity->$field['name'];
        }

        if (in_array($controlType, ['date', 'time']) && array_key_exists('format', $field) && !empty($value)) {
            if ($value instanceof Date) {
                $value = $value->format($field['format']);
            } else {
                $value = date($field['format'], strtotime($value));
            }
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
                    Log::write('error', $controlType);
                }
            }
        } else {
            pr('There are no elements');
        }

        return $html;
    }

    public function renderViewElements()
    {
        $html = '';

        $row = <<<EOT
<div class="row">
    <div class="col-xs-6 col-md-3 form-label">%s</div>
    <div class="form-input">%s</div>
</div>
EOT;

        $fields = $this->_View->get('elements');
        $data = $this->_View->get('data');

        $excludedTypes = ['hidden'];

        foreach ($fields as $field => $attr) {
            $controlType = $attr['controlType'];
            $isVisible = $attr['visible'];

            if (in_array($controlType, $excludedTypes) || $isVisible == false) {
                continue;
            }

            $label = $attr['label'];
            $value = $this->getValue($data, $attr);

            $html .= sprintf($row, $label, $value);
        }
        return $html;
    }

    private function extractHtmlAttributes(array $field)
    {
        $htmlAttr = [
            'label', 'readonly', 'disabled',
            'options', 'value', 'maxlength',
            'required'
        ];

        $options = [];
        foreach ($htmlAttr as $attr) {
            if (!empty($field[$attr])) {
                $options[$attr] = $field[$attr];
            }
        }
        return $options;
    }

    private function string(array $field, $data)
    {
        $options = $this->extractHtmlAttributes($field);
        $options['type'] = 'text';

        $value = $this->Form->input($field['aliasField'], $options);
        return $value;
    }

    private function integer(array $field, $data)
    {
        $options = $this->extractHtmlAttributes($field);
        $options['type'] = 'number';
        $html = '';

        if (array_key_exists('disabled', $options) && array_key_exists('displayFrom', $field)) {
            unset($options['disabled']);
            $value = $this->getValue($data, $field);
            $options['type'] = 'hidden';
            $html .= $this->Form->input($field['name'].'_name', ['value' => $value, 'disabled' => 'disabled', 'label' => $field['label']]);
        }

        $html .= $this->Form->input($field['aliasField'], $options);

        return $html;
    }

    private function float(array $field, $data)
    {
        return $this->integer($field);
    }

    private function textarea(array $field, $data)
    {
        $options = $this->extractHtmlAttributes($field);
        $options['type'] = 'textarea';

        return $this->Form->input($field['aliasField'], $options);
    }

    private function dropdown(array $field, $data)
    {
        $options = $this->extractHtmlAttributes($field);
        $options['type'] = 'select';

        return $this->Form->input($field['aliasField'], $options);
    }

    private function hidden(array $field, $data)
    {
        $options = $this->extractHtmlAttributes($field);
        $options['type'] = 'hidden';

        return $this->Form->input($field['aliasField'], $options);
    }

    private function date(array $field, $data)
    {
        $options = ['type' => 'text', 'class' => 'form-control', 'label' => false];
        $required = $field['required'];
        $value = $field['value'];

        if ($required) {
            $options['required'] = 'required';
        }

        if (!empty($value)) {
            $options['value'] = $value;
        } else {
            if ($required) {
                $options['value'] = date('d-m-Y', time());
            }
        }

        $attr = [
            'id' => $field['model'] . '_' . $field['name'],
            'name' => $field['aliasField'],
            'label' => $field['label'],
            'options' => $options,
            'required' => $required ? ' required' : ''
        ];

        // datepicker variable is used for initialising javascript in datepicker.ctp
        if (!is_null($this->_View->get('datepicker'))) {
            $datepickers = $this->_View->get('datepicker');
            $datepickers[] = $attr;
            $this->_View->set('datepicker', $datepickers);
        } else {
            $this->_View->set('datepicker', [$attr]);
        }

        $this->includes['datepicker']['include'] = true;

        return $this->_View->element('Page.date', $attr);
    }

    // private function time(array $field)
    // {
    //     $options = ['type' => 'text', 'class' => 'form-control', 'label' => false];
    //     $required = $field['required'];
    //     $value = $field['value'];

    //     if ($required) {
    //         $options['required'] = 'required';
    //     }

    //     if (!empty($value)) {
    //         $options['value'] = $value;
    //     } else {
    //         if ($required) {
    //             $options['value'] = date('d-m-Y', time());
    //         }
    //     }

    //     $attr = [
    //         'id' => $field['model'] . '_' . $field['name'],
    //         'name' => $field['aliasField'],
    //         'label' => $field['label'],
    //         'options' => $options,
    //         'required' => $required ? ' required' : ''
    //     ];

    //     // datepicker variable is used for initialising javascript in datepicker.ctp
    //     if (!is_null($this->_View->get('datepicker'))) {
    //         $datepickers = $this->_View->get('datepicker');
    //         $datepickers[] = $attr;
    //         $this->_View->set('datepicker', $datepickers);
    //     } else {
    //         $this->_View->set('datepicker', [$attr]);
    //     }

    //     $this->includes['datepicker']['include'] = true;

    //     return $this->_View->element('../Page/date', $attr);
    // }

    public function table(array $field, $data)
    {
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

        $headers = $this->Html->tableHeaders($attr['headers']);
        $cells = $this->Html->tableCells($attr['cells']);

        $html = sprintf($html, $field['label'], $headers, $cells);
        return $html;
    }

    public function afterRender()
    {
        $this->includes();
    }
}
