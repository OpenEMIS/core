<?php
namespace ControllerAction\View\Helper;

use ArrayObject;
use Cake\View\UrlHelper;
use Cake\Event\Event;
use Cake\View\Helper;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\I18n\Time;
use Cake\I18n\Date;
use Cake\I18n\I18n;
use Cake\View\Helper\IdGeneratorTrait;
use Cake\View\NumberHelper;
use Cake\Network\Session;
use Cake\Utility\Hash;

use Cake\Log\Log;

class HtmlFieldHelper extends Helper
{
    use IdGeneratorTrait;


    public $table = null;

    public $helpers = ['ControllerAction', 'Html', 'Form', 'Url', 'Number'];

    public $includes = [
        'datepicker' => [
            'include' => false,
            'css' => 'ControllerAction.../plugins/datepicker/css/bootstrap-datepicker.min',
            'js' => 'ControllerAction.../plugins/datepicker/js/bootstrap-datepicker.min',
            'element' => 'ControllerAction.bootstrap-datepicker/datepicker'
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

    public function renderElement($element, $attr)
    {
        return $this->_View->element($element, $attr);
    }

    public function viewSet($element, $attr)
    {
        if (!is_null($this->_View->get($element))) {
            $options = $this->_View->get($element);
            $options[] = $attr;
            $this->_View->set($element, $options);
        } else {
            $this->_View->set($element, [$attr]);
        }
    }

    private function patchInvalidFields($data, $field, $options)
    {
        if (!is_null($data)) {
            $invalid = $data->invalid();
            if (!empty($invalid) && array_key_exists($field, $invalid)) {
                $options['value'] = $data->invalid($field);
            }
        }
        if (array_key_exists('label', $options)) {
            if (!is_array($options['label'])) {
                $options['label'] = ['escape' => false, 'text' => $options['label']];
            }
        }
        return $options;
    }

    public function dispatchEvent($subject, $eventKey, $method = null, $params = [])
    {
        $eventMap = $subject->implementedEvents();
        $event = new Event($eventKey, $this, $params);

        if (!array_key_exists($eventKey, $eventMap) && !is_null($method)) {
            if (method_exists($subject, $method) || $subject->behaviors()->hasMethod($method)) {
                $subject->eventManager()->on($eventKey, [], [$subject, $method]);
            }
        }
        return $subject->eventManager()->dispatch($event);
    }

    public function render($type, $action, Entity $data, array $attr, array $options = [])
    {
        $html = '';

        if (is_null($this->table)) {
            $this->table = TableRegistry::get($attr['className']);
        }

        // trigger event for custom field types
        $method = 'onGet' . Inflector::camelize($type) . 'Element';
        $eventKey = 'ControllerAction.Model.' . $method;
        $event = $this->dispatchEvent($this->table, $eventKey, $method, ['action' => $action, 'entity' => $data, 'attr' => $attr, 'options' => $options]);

        if (isset($event->result)) {
            $html = $event->result;
        } else {
            if (method_exists($this, $type)) {
                $html = $this->$type($action, $data, $attr, $options);
            }
        }
        return $html;
    }

    public function includes($action, $table = null)
    {
        $includes = new ArrayObject($this->includes);

        if (!is_null($table)) {
            // trigger event to update inclusion of css/js files
            $eventKey = 'ControllerAction.Model.onUpdateIncludes';
            $event = $this->dispatchEvent($table, $eventKey, null, [$includes, $action]);
        }

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
                    echo $this->_View->element($include['element']);
                }
            }
        }
    }

    // Elements definition starts here

    public function string($action, Entity $data, $attr, $options = [])
    {
        $value = '';
        if ($action == 'index' || $action == 'view') {
            $fieldName = array_key_exists('fieldName', $attr) ? $attr['fieldName'] : $attr['field'];
            $value = Hash::get($data, $fieldName, '');
        } elseif ($action == 'edit') {
            $options['type'] = 'string';
            if (array_key_exists('length', $attr)) {
                $options['maxlength'] = $attr['length'];
            }
            $fieldName = $attr['model'] . '.' . $attr['field'];
            if (array_key_exists('fieldName', $attr)) {
                $fieldName = $attr['fieldName'];
            }
            $options = $this->patchInvalidFields($data, $attr['field'], $options);
            $value = $this->Form->input($fieldName, $options);
        }
        return $value;
    }

    public function decimal($action, Entity $data, $attr, $options = [])
    {
        $value = '';
        if ($action == 'index' || $action == 'view') {
            $value = $data->{$attr['field']};
        } elseif ($action == 'edit') {
            $value = $this->string($action, $data, $attr, $options);
        }
        return $value;
    }

    public function float($action, Entity $data, $attr, $options = [])
    {
        $value = '';
        if ($action == 'index' || $action == 'view') {
            $value = $data->{$attr['field']};
            //check whether value is float
            if (is_float($value)) {
                $value = sprintf('%0.2f', $value);
            }
        } elseif ($action == 'edit') {
            $options['type'] = 'number';
            $fieldName = $attr['model'] . '.' . $attr['field'];
            if (array_key_exists('fieldName', $attr)) {
                $fieldName = $attr['fieldName'];
            }
            $options = $this->patchInvalidFields($data, $attr['field'], $options);
            $value = $this->Form->input($fieldName, $options);
        }
        return $value;
    }

    public function integer($action, Entity $data, $attr, $options = [])
    {
        $value = '';
        if ($action == 'index' || $action == 'view') {
            $fieldName = array_key_exists('fieldName', $attr) ? $attr['fieldName'] : $attr['field'];
            $value = Hash::get($data, $fieldName, '');
        } elseif ($action == 'edit') {
            $options['type'] = 'number';
            $fieldName = $attr['model'] . '.' . $attr['field'];
            if (array_key_exists('fieldName', $attr)) {
                $fieldName = $attr['fieldName'];
            }
            $options = $this->patchInvalidFields($data, $attr['field'], $options);
            $value = $this->Form->input($fieldName, $options);
        }
        return $value;
    }

    public function password($action, Entity $data, $attr, $options = [])
    {
        $value = '';
        if ($action == 'index' || $action == 'view') {
            if (!empty($data->{$attr['field']})) {
                $value = '***************';
            }
        } elseif ($action == 'edit') {
            $options['type'] = 'password';
            $fieldName = $attr['model'] . '.' . $attr['field'];
            if (array_key_exists('fieldName', $attr)) {
                $fieldName = $attr['fieldName'];
            }
            $options = $this->patchInvalidFields($data, $attr['field'], $options);
            $value = $this->Form->input($fieldName, $options);
        }
        return $value;
    }

    public function select($action, Entity $data, $attr, $options = [])
    {
        $value = '';
        if ($action == 'index' || $action == 'view') {
            $fieldName = array_key_exists('fieldName', $attr) ? $attr['fieldName'] : $attr['field'];
            $selectedOption = Hash::get($data, $fieldName, '');

            if (!empty($attr['options'])) {
                if ($selectedOption === '') {
                    return '';
                } else {
                    if (array_key_exists($selectedOption, $attr['options'])) {
                        $value = $attr['options'][$selectedOption];

                        if (is_array($value)) {
                            $value = $value['text'];
                        } else {
                            $value = $value;
                        }
                    }
                }
            }

            if (empty($value)) {
                $value = $selectedOption;
            }

            if (!isset($attr['translate']) || (isset($attr['translate']) && $attr['translate'])) {
                $value = __($value);
            }
        } elseif ($action == 'edit') {
            if (array_key_exists('empty', $attr)) {
                if ($attr['empty'] === true) {
                    $options['empty'] = '-- ' . __('Select') . ' --';
                } else {
                    $options['empty'] = '-- ' . __($attr['empty']) . ' --';
                }
            }
            if (isset($attr['options'])) {
                if (!empty($attr['options'])) {
                    if (isset($attr['default'])) {
                        $options['default'] = $attr['default'];
                    }
                }
                $options['options'] = $attr['options'];
            }
            if (isset($attr['attr'])) {
                $options = array_merge($options, $attr['attr']);
            }

            $fieldName = $attr['model'] . '.' . $attr['field'];
            if (array_key_exists('fieldName', $attr)) {
                $fieldName = $attr['fieldName'];
            }
            $value = $this->secureSelect($fieldName, $options, $attr);
        }
        return $value;
    }

    public function secureSelect($fieldName, $options, $attr = [])
    {
        $arrayKeys = [];
        $list = [];
        foreach ($options['options'] as $key => $opt) {
            if (is_array($opt) && isset($opt['text'])) {
                if (!isset($attr['translate']) || (isset($attr['translate']) && $attr['translate'])) {
                    $opt['text'] = __($opt['text']);
                } else {
                    $opt['text'] = $opt['text'];
                }
                $list[$key] = $opt;
                if (!in_array('disabled', $opt, true)) {
                    if (isset($opt['value'])) {
                        $arrayKeys[] = $opt['value'];
                    } else {
                        $arrayKeys[] = $key;
                    }
                }
            } elseif (is_array($opt)) {
                $subList = [];
                foreach ($opt as $k => $subOption) {
                    if (is_array($subOption) && isset($subOption['text'])) {
                        if (!isset($attr['translate']) || (isset($attr['translate']) && $attr['translate'])) {
                            $subOption['text'] = __($subOption['text']);
                        } else {
                            $subOption['text'] = $subOption['text'];
                        }
                        $subList[$k] = $subOption;
                    } else {
                        if (!isset($attr['translate']) || (isset($attr['translate']) && $attr['translate'])) {
                            $subList[$k] = __($subOption);
                        } else {
                            $subList[$k] = $subOption;
                        }
                    }
                }

                if (!isset($attr['translate']) || (isset($attr['translate']) && $attr['translate'])) {
                    $list[__($key)] = $subList;
                } else {
                    $list[$key] = $subList;
                }

                $arrayKeys = array_merge($arrayKeys, array_keys($subList));
            } else {
                if (!isset($attr['translate']) || (isset($attr['translate']) && $attr['translate'])) {
                    $list[$key] = __($opt);
                } else {
                    $list[$key] = $opt;
                }
                $arrayKeys[] = $key;
            }
        }
        $options['options'] = $list;
        if (isset($options['empty'])) {
            $arrayKeys[] = '';
        }
        $session = $this->request->session();
        $session->write('FormTampering.'.$fieldName, $arrayKeys);
        $options['type'] = 'select';
        $value = $this->Form->input($fieldName, $options);
        return $value;
    }

    public function slider($action, Entity $data, $attr, $options = [])
    {
        $value = '';
        if ($action == 'index' || $action == 'view') {
            $fieldName = array_key_exists('fieldName', $attr) ? $attr['fieldName'] : $attr['field'];
            $value = Hash::get($data, $fieldName, 0);
        } else {
            if (!isset($attr['min'])) {
                $attr['min'] = 0;
            }
            if (!isset($attr['max'])) {
                $attr['max'] = 10;
            }
            if (!isset($attr['step'])) {
                $attr['step'] = 0.5;
            }
            $attr['rating'] = Hash::get($data, $attr['field'], $attr['min']);

            $fieldName = $attr['model'] . '.' . $attr['field'];
            if (array_key_exists('fieldName', $attr)) {
                $fieldName = $attr['fieldName'];
            }
            $attr['fieldName'] = $fieldName;
            $value = $this->_View->element('ControllerAction.slider_input', ['attr' => $attr]);
        }
        return $value;
    }

    public function text($action, Entity $data, $attr, $options = [])
    {
        $value = '';
        if ($action == 'index' || $action == 'view') {
            $fieldName = array_key_exists('fieldName', $attr) ? $attr['fieldName'] : $attr['field'];
            $value = nl2br(Hash::get($data, $fieldName, ''));
        } elseif ($action == 'edit') {
            $options['type'] = 'textarea';
            $fieldName = $attr['model'] . '.' . $attr['field'];
            if (array_key_exists('fieldName', $attr)) {
                $fieldName = $attr['fieldName'];
            }
            $options = $this->patchInvalidFields($data, $attr['field'], $options);
            $value = $this->Form->input($fieldName, $options);
        }
        return $value;
    }

    public function hidden($action, Entity $data, $attr, $options = [])
    {
        $value = '';
        if ($action == 'view') {
            // no logic required
        } elseif ($action == 'edit') {
            $options['type'] = 'hidden';
            if (array_key_exists('value', $attr)) {
                $options['value'] = $attr['value'];
            }
            $fieldName = $attr['model'] . '.' . $attr['field'];
            if (array_key_exists('fieldName', $attr)) {
                $fieldName = $attr['fieldName'];
            }
            $options = $this->patchInvalidFields($data, $attr['field'], $options);
            $value = $this->Form->input($fieldName, $options);
        }
        return $value;
    }

    public function readonly($action, Entity $data, $attr, $options = [])
    {
        $value = '';
        if ($action == 'view' || $action == 'index') {
            if (array_key_exists('value', $attr)) {
                $value = $attr['value'];
            } else {
                $value = $data->{$attr['field']};
            }
        } elseif ($action == 'edit') {
            $value = $this->disabled($action, $data, $attr, $options);
            unset($options['disabled']);
            unset($options['value']);
            $value .= $this->hidden($action, $data, $attr, $options);
        }
        return $value;
    }

    public function disabled($action, Entity $data, $attr, $options = [])
    {
        $value = '';
        if ($action == 'index' || $action == 'view') {
            if (array_key_exists('value', $attr)) {
                $value = $attr['value'];
            } else {
                $value = $data->{$attr['field']};
            }
        } elseif ($action == 'edit') {
            $options['type'] = 'text';
            $options['disabled'] = 'disabled';
            $field = $attr['field'];
            $invalid = $data->invalid();

            if (isset($attr['options']) && !isset($attr['attr']['value'])) {
                if (!empty($invalid) && array_key_exists($field, $invalid)) {
                    $options['value'] = $attr['options'][$data->invalid($field)];
                } else {
                    $options['value'] = $attr['options'][$data->{$field}];
                }
            } elseif (isset($attr['attr']['value'])) {
                $options['value'] = $attr['attr']['value'];
            } else {
                if (!empty($invalid) && array_key_exists($field, $invalid)) {
                    $options['value'] = $data->invalid($field);
                } else {
                    $options['value'] = $data->{$field};
                }
            }
            $fieldName = $attr['model'] . '.' . $field;
            if (array_key_exists('fieldName', $attr)) {
                $fieldName = $attr['fieldName'];
            }
            $value = $this->Form->input($fieldName, $options);
        }
        return $value;
    }

    public function image($action, Entity $data, $attr, $options = [])
    {
        $value = '';
        $defaultWidth = 90;
        $defaultHeight = 115;

        $maxImageWidth = 60;

        if ($action == 'index' || $action == 'view') {
            $src = '';

            if ($data->has($attr['field'])) {
                $src = $data[$attr['field']];
            }

            if (array_key_exists('ajaxLoad', $attr) && $attr['ajaxLoad']) {
                $imageUrl = '';
                if (array_key_exists('imageUrl', $attr) && $attr['imageUrl']) {
                    $imageUrl = $this->Url->build($attr['imageUrl'], true);
                }
                $imageDefault = (array_key_exists('imageDefault', $attr) && $attr['imageDefault'])? '<i class='.$attr['imageDefault'].'></i>': '';
                $value= '<div class="table-thumb"
					data-load-image=true
					data-image-width='.$maxImageWidth.'
					data-image-url='.$imageUrl.'
					>
					<div class="profile-image-thumbnail">
					'.$imageDefault.'
					</div>
					</div>';
            } else {
                if (!empty($src)) {
                    if (is_resource($src)) {
                        $src = base64_encode(stream_get_contents($src));
                    }
                    $value = (base64_decode($src, true)) ? '<div class="table-thumb"><img src="data:image/jpeg;base64,'.$src.'" style="max-width:'.$maxImageWidth.'px;" /></div>' : $src;
                }
            }
        } elseif ($action == 'edit') {
            $defaultImgViewClass = $this->table->getDefaultImgViewClass();
            $defaultImgMsg = $this->table->getDefaultImgMsg();
            $defaultImgView = $this->table->getDefaultImgView();

            $showRemoveButton = false;
            if (isset($data[$attr['field']]['tmp_name'])) {
                $tmp_file = ((is_array($data[$attr['field']])) && (file_exists($data[$attr['field']]['tmp_name']))) ? $data[$attr['field']]['tmp_name'] : "";
                $tmp_file_read = (!empty($tmp_file)) ? file_get_contents($tmp_file) : "";
            } else {
                $tmp_file = true;
                $tmp_file_read = $data[$attr['field']];
            }

            if (!is_resource($tmp_file_read)) {
                $src = (!empty($tmp_file_read)) ? '<img id="existingImage" class="'.$defaultImgViewClass.'" src="data:image/jpeg;base64,'.base64_encode($tmp_file_read).'"/>' : $defaultImgView;
                $showRemoveButton = (!empty($tmp_file)) ? true : false;
            } else {
                $tmp_file_read = stream_get_contents($tmp_file_read);
                $src = (!empty($tmp_file_read)) ? '<img id="existingImage" class="'.$defaultImgViewClass.'" src="data:image/jpeg;base64,'.base64_encode($tmp_file_read).'"/>' : $defaultImgView;
                $showRemoveButton = true;
            }
            header('Content-Type: image/jpeg');

            if (isset($attr['defaultWidth'])) {
                $defaultWidth = $attr['defaultWidth'];
            }
            if (isset($attr['defaultHeight'])) {
                $defaultWidth = $attr['defaultHeight'];
            }

            $this->includes['jasny']['include'] = true;
            $value = $this->_View->element('ControllerAction.bootstrap-jasny/image_uploader', ['attr' => $attr, 'src' => $src,
                                                                                            'defaultWidth' => $defaultWidth,
                                                                                            'defaultHeight' => $defaultHeight,
                                                                                            'showRemoveButton' => $showRemoveButton,
                                                                                            'defaultImgMsg' => $defaultImgMsg,
                                                                                            'defaultImgView' => $defaultImgView]);
        }

        return $value;
    }

    public function download($action, Entity $data, $attr, $options = [])
    {
        $value = '';
        if ($action == 'index' || $action == 'view') {
            $value = $this->Html->link($data->{$attr['field']}, $attr['attr']['url']);
        } elseif ($action == 'edit') {
        }
        return $value;
    }

    public function element($action, Entity $data, $attr, $options = [])
    {
        $value = '';

        $element = $attr['element'];

        $attr['id'] = $attr['model'] . '_' . $attr['field'];
        $attr['label'] = array_key_exists('label', $options) ? $options['label'] : Inflector::humanize($attr['field']);
        $value = $this->_View->element($element, ['entity' => $data, 'attr' => $attr]);
        return $value;
    }

    public function dateTime($action, Entity $data, $attr, $options = [])
    {
        $value = '';
        $_options = [
            'format' => 'dd-mm-yyyy H:i:s',
            'todayBtn' => 'linked',
            'orientation' => 'auto'
        ];

        if (!isset($attr['date_options'])) {
            $attr['date_options'] = [];
        }

        $field = $attr['field'];
        if (!is_null($data)) {
            $invalid = $data->invalid();
            if (!empty($invalid) && array_key_exists($field, $invalid)) {
                $value = $data->invalid($field);
            } else {
                $value = $data->{$field};
            }
        }

        if ($action == 'index' || $action == 'view') {
            if (!is_null($value)) {
                $table = TableRegistry::get($attr['className']);
                $event = new Event('ControllerAction.Model.onFormatDateTime', $this, compact('value'));
                $event = $table->eventManager()->dispatch($event);
                if (strlen($event->result) > 0) {
                    $value = $event->result;
                }
            }
        }
        return $value;
    }

    public function date($action, Entity $data, $attr, $options = [])
    {
        $value = '';
        $_options = [
            'format' => 'dd-mm-yyyy',
            'todayBtn' => 'linked',
            'orientation' => 'auto',
            'autoclose' => true,
        ];

        $field = $attr['field'];
        $defaultDate = true;

        if (isset($attr['className'])) {
            $table = TableRegistry::get($attr['className']);
            $schema = $table->schema();
            $columnAttr = $schema->column($field);
            if ($columnAttr['null'] == true) {
                $defaultDate = date('d-m-Y');
            }
        }

        if (!isset($attr['date_options'])) {
            $attr['date_options'] = [];
        }

        if (!isset($attr['default_date'])) {
            $attr['default_date'] = $defaultDate;
        }

        if (!is_null($data)) {
            $invalid = $data->invalid();
            if (!empty($invalid) && array_key_exists($field, $invalid)) {
                $value = $data->invalid($field);
            } else {
                $value = $data->{$field};
            }
        }

        if ($action == 'index' || $action == 'view') {
            if (!is_null($value)) {
                $event = new Event('ControllerAction.Model.onFormatDate', $this, compact('value'));
                $event = $table->eventManager()->dispatch($event);
                if (strlen($event->result) > 0) {
                    $value = $event->result;
                }
            }
        } elseif ($action == 'edit') {
            if (!array_key_exists('id', $attr)) {
                $attr['id'] = $attr['model'] . '_' . $field;
                if (array_key_exists('fieldName', $attr)) {
                    $attr['id'] = $this->_domId($attr['fieldName']);
                }
            }

            $attr['date_options'] = array_merge($_options, $attr['date_options']);
            if (!array_key_exists('value', $attr)) {
                if (!empty($value)) {
                    if (is_object($value)) {
                        $attr['value'] = $value->format('d-m-Y');
                    } else {
                        $attr['value'] = date('d-m-Y', strtotime($value));
                    }
                } elseif ($attr['default_date']) {
                    $attr['value'] = date('d-m-Y');
                }
            } else {
                if (is_object($attr['value'])) {
                    $attr['value'] = $attr['value']->format('d-m-Y');
                } elseif (!array_key_exists('special_value', $attr)) {
                    $attr['value'] = date('d-m-Y', strtotime($attr['value']));
                }
                // else $attr['value'] will be what was set before calling this function when $attr['special_value'] was set to true.
                // this is added when datepicker input is being used with angularJs scope
            }

            if (!is_null($this->_View->get('datepicker'))) {
                $datepickers = $this->_View->get('datepicker');
                $datepickers[] = $attr;
                $this->_View->set('datepicker', $datepickers);
            } else {
                $this->_View->set('datepicker', [$attr]);
            }
            $value = $this->_View->element('ControllerAction.bootstrap-datepicker/datepicker_input', ['attr' => $attr]);
            $this->includes['datepicker']['include'] = true;
        }
        return $value;
    }

    public function time($action, Entity $data, $attr, $options = [])
    {
        $value = '';
        $_options = [
            'defaultTime' => false
        ];

        if (!isset($attr['time_options'])) {
            $attr['time_options'] = [];
        }
        if (!isset($attr['default_time'])) {
            $attr['default_time'] = true;
        }

        $field = $attr['field'];

        if (!is_null($data)) {
            $invalid = $data->invalid();
            if (!empty($invalid) && array_key_exists($field, $invalid)) {
                $value = $data->invalid($field);
            } else {
                $value = $data->{$field};
            }
        }

        if ($action == 'index' || $action == 'view') {
            if (!is_null($value)) {
                $table = TableRegistry::get($attr['className']);
                $event = new Event('ControllerAction.Model.onFormatTime', $this, compact('value'));
                $event = $table->eventManager()->dispatch($event);
                if (strlen($event->result) > 0) {
                    $value = $event->result;
                }
            }
        } elseif ($action == 'edit') {
            if (!isset($attr['id'])) {
                $attr['id'] = $attr['model'] . '_' . $field;
            }

            if (array_key_exists('fieldName', $attr)) {
                $attr['id'] = $this->_domId($attr['fieldName']);
            }
            $attr['time_options'] = array_merge($_options, $attr['time_options']);

            if (!array_key_exists('value', $attr)) {
                if (!is_null($value)) {
                    $attr['value'] = date('h:i A', strtotime($value));
                    $attr['time_options']['defaultTime'] = $attr['value'];
                } elseif ($attr['default_time']) {
                    $attr['time_options']['defaultTime'] = date('h:i A');
                }
            } else {
                if ($attr['value'] instanceof Time) {
                    $attr['value'] = $attr['value']->format('h:i A');
                    $attr['time_options']['defaultTime'] = $attr['value'];
                } else {
                    $attr['value'] = date('h:i A', strtotime($attr['value']));
                    $attr['time_options']['defaultTime'] = $attr['value'];
                }
            }

            if (!is_null($this->_View->get('timepicker'))) {
                $timepickers = $this->_View->get('timepicker');
                $timepickers[] = $attr;
                $this->_View->set('timepicker', $timepickers);
            } else {
                $this->_View->set('timepicker', [$attr]);
            }
            $value = $this->_View->element('ControllerAction.bootstrap-timepicker/timepicker_input', ['attr' => $attr]);
            $this->includes['timepicker']['include'] = true;
        }

        return $value;
    }

    public function chosenSelect($action, Entity $data, $attr, $options = [])
    {
        $value = '';
        if ($action == 'index' || $action == 'view') {
            $value = $data->{$attr['field']};
            $chosenSelectList = [];
            if (!empty($value)) {
                if (is_array($value)) {
                    foreach ($value as $obj) {
                        $chosenSelectList[] = $obj->name;
                    }
                    $value = implode(', ', $chosenSelectList);
                }
            } else {
                $value = isset($attr['valueWhenEmpty']) ? $attr['valueWhenEmpty'] : '';
            }
        } elseif ($action == 'edit') {
            $value = $this->chosenSelectInput($attr, $options);
        }
        return $value;
    }

    public function chosenSelectInput($attr, $options = [])
    {
        $_options = [
            'class' => 'chosen-select',
            'multiple' => 'true',
            'type' => 'select'
        ];
        
        $Locales = TableRegistry::get('Locales');
        $langDir = $Locales->getLangDir(I18n::locale());

        if ($langDir == 'rtl') {
            $_options['class'] = 'chosen-select chosen-rtl';
        }
        
        $_options['options'] = isset($attr['options']) ? $attr['options'] : [];
        $_options['data-placeholder'] = isset($attr['placeholder']) ? $attr['placeholder'] : '';
        $options = array_merge($_options, $options);

        $this->includes['chosen']['include'] = true;

        $fieldName = $attr['model'] . '.' . $attr['field'];
        if (array_key_exists('fieldName', $attr)) {
            $fieldName = $attr['fieldName'];
        } else {
            if ($options['multiple']) {
                $fieldName = $attr['model'] . '.' . $attr['field'] . '._ids';
            } else {
                $fieldName = $attr['model'] . '.' . $attr['field'];
            }
        }

        if ($options['multiple']) {
            //logic when there is no option on multiple chosen select which unselectable
            if (isset($options['empty'])) {
                unset($options['empty']);

                $options['options'][] = [
                    'text' => __('No options'),
                    'value' => '',
                    'disabled' => 'disabled'
                ];
            }
        }
        return $this->Form->input($fieldName, $options);
    }

    public function binary($action, Entity $data, $attr, $options = [])
    {
        $value = '';
        $table = TableRegistry::get($attr['className']);
        $fileUpload = $table->behaviors()->get('FileUpload');
        $name = '&nbsp;';
        if (!empty($fileUpload)) {
            $name = $fileUpload->config('name');
        }

        if ($action == 'index' || $action == 'view') {
            // Modified logic
            // $buttons = $this->_View->get('_buttons');
            $buttons = $this->_View->get('ControllerAction');
            if (array_key_exists('buttons', $buttons)) { // for CAv3
                $action = $buttons['buttons']['download']['url'];
            } else { // for CAv4
                $action = $buttons['table']->url('download', false);
            }

            // New logic from master
            // $buttons = $this->_View->get('ControllerAction');
            // $buttons = $buttons['buttons'];
            // $action = $buttons['download']['url'];
            $request = $this->request;
            $ids = $this->ControllerAction->getIdKeys($table, $data, false);
            $action = ['action' => $request->action, 'download', $this->ControllerAction->paramsEncode($ids)];
            $value = $this->link($data->{$name}, $action);
        } elseif ($action == 'edit') {
            $this->includes['jasny']['include'] = true;
            if (isset($data->{$name})) {
                $attr['value'] = $data->{$name};
            }
            $value = $this->_View->element('ControllerAction.file_input', ['attr' => $attr]);
        }
        return $value;
    }

    public function color($action, Entity $data, $attr, $options = [])
    {
        $value = '';
        if ($action == 'index' || $action == 'view') {
            $value = '<div style="background-color:'.$data->{$attr['field']}.'">&nbsp;</div>';
        } elseif ($action == 'edit') {
            $options['type'] = 'color';
            $options['onchange'] = 'clickColor(0, -1, -1, 5);';
            $fieldName = $attr['model'] . '.' . $attr['field'];
            if (array_key_exists('fieldName', $attr)) {
                $fieldName = $attr['fieldName'];
            }
            $value = $this->Form->input($fieldName, $options);
        }
        return $value;
    }

    public function autocomplete($action, Entity $data, $attr, &$options = [])
    {
        $value = '';
        if ($action == 'index' || $action == 'view') {
            $value = $data->{$attr['field']};
        } elseif ($action == 'edit') {
            $options['type'] = 'string';
            $options['class'] = "form-control autocomplete form-error ui-autocomplete-input";
            if (array_key_exists('length', $attr)) {
                $options['maxlength'] = $attr['length'];
            }
            if (array_key_exists('placeholder', $attr)) {
                $options['placeholder'] = $attr['placeholder'];
            }
            if (array_key_exists('url', $attr)) {
                $options['url'] = $this->Url->build($attr['url'], true);
            }
            $fieldName = $attr['model'] . '.' . $attr['field'];
            if (array_key_exists('fieldName', $attr)) {
                $fieldName = $attr['fieldName'];
            }

            $value = $this->_View->element('ControllerAction.autocomplete', ['attr' => $attr, 'options' => $options]);
            $fieldName = $attr['model'] . '.' . $attr['field'];
            $this->Form->unlockField($fieldName);
        }
        return $value;
    }

    public function table($action, Entity $data, $attr, $options = [])
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

        $html = sprintf($html, $attr['label'], $headers, $cells);
        return $html;
    }

    public function escapeHtmlEntity($text)
    {
        $htmlInfo = htmlentities($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $htmlInfo = str_replace('/', '&#x2F;', $htmlInfo);
        return $htmlInfo;
    }

    public function decodeEscapeHtmlEntity($encodedText)
    {
        $htmlInfo = str_replace('&#x2F;', '/', $encodedText);
        $htmlInfo = html_entity_decode($htmlInfo, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return $htmlInfo;
    }

    public function link($title, $url = null, array $options = [])
    {
        $title = $this->decodeEscapeHtmlEntity($title);
        return $this->Html->link($title, $url, $options);
    }

    // a template function for creating new elements
    public function test($action, Entity $data, $attr, $options = [])
    {
        $value = '';
        if ($action == 'index' || $action == 'view') {
        } elseif ($action == 'edit') {
        }
        return $value;
    }

    // Elements definition ends here
}
