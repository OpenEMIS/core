<?php
namespace ControllerAction\Model\Traits;

use Cake\Utility\Inflector;

trait UtilityTrait
{
    public function endsWith($haystack, $needle)
    {
        return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
    }

    /**
     * Converts the class alias to a label.
     *
     * Usefull for class names or aliases that are more than a word.
     * Converts the camelized word to a sentence.
     * If null, this function will used the default alias of the current model.
     *
     * @param  string $camelizedString the camelized string [optional]
     * @return string                  the converted string
     */
    public function getHeader($camelizedString = null)
    {
        $header = '';
        if ($camelizedString) {
            $header = Inflector::humanize(Inflector::underscore($camelizedString));
        } else {
            $header = Inflector::humanize(Inflector::underscore($this->alias()));
        }
        return __($header);
    }

    // PHP 5.5 array_column alternative
    public function array_column($array, $column_name)
    {
        return array_map(
            function ($element) use ($column_name) {
                if (isset($element[$column_name])) {
                    return $element[$column_name];
                }
            }, $array);
    }

    // to get the value from querystring, if exists. otherwise get a default value from the first option in the list
    public function queryString($key, $options = [], $request = null)
    {
        $value = null;
        $defaultValue = null;
        if (is_null($request) && isset($this->request)) {
            $request = $this->request;
        }

        if (!is_null($request)) {
            $query = $request->query;

            if (isset($query[$key])) {
                $value = $query[$key];
            }
            $found = false;
            if (!array_key_exists($value, $options)) {
                foreach ($options as $i => $val) {
                    if (is_array($val)) {
                        if (array_key_exists('value', $val) && array_key_exists('text', $val)) { // cake format ['value', 'text']
                            if (is_null($defaultValue)) {
                                $defaultValue = $val['value'];
                            }

                            if ($val['value'] === $value) {
                                $found = true;
                                break;
                            }
                        } else { // option group exists
                            if (is_null($defaultValue)) {
                                $defaultValue = key($val);
                            }

                            if (array_key_exists($value, $val)) {
                                $found = true;
                                break;
                            }
                        }
                    } else { // normal array
                        if (is_null($defaultValue)) {
                            $defaultValue = $i;
                        }

                        if ($value === $i) {
                            $found = true;
                            break;
                        }
                    }
                }

                if (!$found) {
                    $value = $defaultValue;
                }
            }
            $request->query[$key] = $value;
        }
        return $value;
    }

    /**
     * Generates a random password base on the requirements.
     * Credit to https://www.dougv.com/2010/03/a-strong-password-generator-written-in-php/
     *
     * @param integer $l Number of character for password.
     * @param integer $c Number of uppercase character for password.
     * @param integer $n Number of numerical character for password.
     * @param integer $s Number of special character for password.
     * @return string Random password
     */
    public function generatePassword($l = 6, $c = 0, $n = 0, $s = 0)
    {
        $out = '';
        // get count of all required minimum special chars
        $count = $c + $n + $s;

        // sanitize inputs; should be self-explanatory
        if (!is_int($l) || !is_int($c) || !is_int($n) || !is_int($s)) {
            trigger_error('Argument(s) not an integer', E_USER_WARNING);
            return false;
        } elseif ($l < 0 || $l > 20 || $c < 0 || $n < 0 || $s < 0) {
            trigger_error('Argument(s) out of range', E_USER_WARNING);
            return false;
        } elseif ($c > $l) {
            trigger_error('Number of password capitals required exceeds password length', E_USER_WARNING);
            return false;
        } elseif ($n > $l) {
            trigger_error('Number of password numerals exceeds password length', E_USER_WARNING);
            return false;
        } elseif ($s > $l) {
            trigger_error('Number of password capitals exceeds password length', E_USER_WARNING);
            return false;
        } elseif ($count > $l) {
            trigger_error('Number of password special characters exceeds specified password length', E_USER_WARNING);
            return false;
        }
        // all inputs clean, proceed to build password

        // change these strings if you want to include or exclude possible password characters
        $chars = "abcdefghijklmnopqrstuvwxyz";
        $caps = strtoupper($chars);
        $nums = "0123456789";
        $syms = "!@#$%^&*()-+?";

        // build the base password of all lower-case letters
        for ($i = 0; $i < $l; $i++) {
            $out .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        // create arrays if special character(s) required
        if ($count) {
            // split base password to array; create special chars array
            $tmp1 = str_split($out);
            $tmp2 = array();

            // Do not change implementation to using mt_rand to rand unless in PHP 7 as rand will have predicable pattern
            // add required special character(s) to second array
            for ($i = 0; $i < $c; $i++) {
                array_push($tmp2, substr($caps, mt_rand(0, strlen($caps) - 1), 1));
            }
            for ($i = 0; $i < $n; $i++) {
                array_push($tmp2, substr($nums, mt_rand(0, strlen($nums) - 1), 1));
            }
            for ($i = 0; $i < $s; $i++) {
                array_push($tmp2, substr($syms, mt_rand(0, strlen($syms) - 1), 1));
            }
            // hack off a chunk of the base password array that's as big as the special chars array
            $tmp1 = array_slice($tmp1, 0, $l - $count);
            // merge special character(s) array with base password array
            $tmp1 = array_merge($tmp1, $tmp2);
            // mix the characters up
            shuffle($tmp1);
            // convert to string for output
            $out = implode('', $tmp1);
        }
        return $out;
    }

    public function advancedSelectOptions(&$options, &$selected, $params = [])
    {
        $callable = array_key_exists('callable', $params) ? $params['callable'] : null;
        $message = array_key_exists('message', $params) ? $params['message'] : '';
        $defaultValue = array_key_exists('defaultValue', $params) ? $params['defaultValue'] : null;
        $selectOption = array_key_exists('selectOption', $params)? $params['selectOption'] : true;

        // Check if the selected key is empty. If it is not empty then change the selected to null and get
        // the first available from the list
        if (is_callable($callable) && !empty($selected)) {
            $count = $callable($selected);
            if ($count == 0) {
                $selected = null;
            }
        }
        foreach ($options as $id => $val) {
            if (is_array($val)) {
                if (array_key_exists('value', $val) && array_key_exists('text', $val)) { // cake format ['value', 'text']

                    // may or may not happen so won't write logic for it yet
                } else { // option group exists
                    foreach ($val as $key => $label) {
                        $label = __($label);
                        $options[$id][$key] = ['value' => $key, 'text' => $label];

                        if (is_null($defaultValue)) {
                            $defaultValue = ['group' => $id, 'selected' => $key];
                        }
                        if (array_key_exists($selected, $options[$id])) {
                            $defaultValue['group'] = $id;
                            $defaultValue['selected'] = $selected;
                        }
                        if (is_callable($callable) && !empty($key)) {
                            $count = $callable($key);
                            if ($count == 0) {
                                if (!empty($message)) {
                                    $options[$id][$key]['text'] = str_replace('{{label}}', $label, $message);
                                }
                                $options[$id][$key][] = 'disabled';

                                if ($selected == $key) {
                                    $selected = null;
                                }
                            } else {
                                if (is_null($defaultValue) || is_null($selected) || $selected == $key) {
                                    $defaultValue = ['group' => $id, 'selected' => $key];
                                    if (is_null($selected)) {
                                        $selected = $key;
                                    }
                                }
                            }
                        }
                    }
                }
            } else { // normal array
                $label = __($val);
                if (strlen($id) > 0) {
                    $options[$id] = ['value' => $id, 'text' => $label];
                }

                if (is_null($defaultValue)) {
                    $defaultValue = ['group' => false, 'selected' => $id];
                }
                if (array_key_exists($selected, $options)) {
                    $defaultValue['group'] = false;
                    $defaultValue['selected'] = $selected;
                }

                if (is_callable($callable) && !empty($id)) {
                    $count = $callable($id);
                    if ($count == 0) {
                        if (!empty($message)) {
                            $options[$id]['text'] = str_replace('{{label}}', $label, $message);
                        }
                        $options[$id][] = 'disabled';

                        if ($selected == $id) {
                            $selected = null;
                        }
                    } else {
                        if (is_null($defaultValue) || is_null($selected) || $selected == $id) {
                            $defaultValue = ['group' => false, 'selected' => $id];
                            if (is_null($selected)) {
                                $selected = $id;
                            }
                        }
                    }
                }
            }
        }
        if (!is_null($defaultValue) && (!is_bool($defaultValue) || !(is_bool($defaultValue) && !$defaultValue))) {
            $selected = $defaultValue['selected'];
            $group = $defaultValue['group'];
            if ($group !== false) {
                if ($selectOption) {
                    $options[$group][$selected][] = 'selected';
                }
            } elseif (strlen($selected) > 0) {
                if ($selectOption) {
                    $options[$selected][] = 'selected';
                }
            }
        }

        return $selected;
    }

    // greatest common denominator function
    public function gCD($a, $b)
    {
        while ($b != 0) {
            $remainder = $a % $b;
            $a = $b;
            $b = $remainder;
        }
        return abs($a);
    }
}
