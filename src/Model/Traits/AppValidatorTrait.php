<?php
namespace App\Model\Traits;

use Cake\Validation\Validation;

trait AppValidatorTrait {

    public static $errors = [];

    public function notBlank($check)
    {
    	return Validation::notBlank($check);
	}

    public function alphaNumeric($check)
    {
        return Validation::alphaNumeric($check);
    }

    public function lengthBetween($check, $min, $max)
    {
        return Validation::lengthBetween($check, $min, $max);
    }

    public function blank($check)
    {
        return Validation::blank($check);
    }

    public function cc($check, $type = 'fast', $deep = false, $regex = null)
    {
        return Validation::cc($check, $type, $deep, $regex);
    }

    public function comparison($check1, $operator = null, $check2 = null)
    {
    	return Validation::comparison($check1, $operator, $check2);
    }

    public function compareWith($check, $field, $context)
    {
        return Validation::compareWith($check, $field, $context);
    }

    public function custom($check, $regex = null)
    {
        return Validation::custom($check, $regex);
    }

    public function date($check, $format = 'ymd', $regex = null)
    {
        return Validation::date($check, $format, $regex);
    }

    public function datetime($check, $dateFormat = 'ymd', $regex = null)
    {
        return Validation::datetime($check, $dateFormat, $regex);
    }

    public function time($check)
    {
        return Validation::time($check);
    }

    public function boolean($check)
    {
        return Validation::boolean($check);
    }

    public function decimal($check, $places = null, $regex = null)
    {
        return Validation::decimal($check, $places, $regex);
    }

    public function email($check, $deep = false, $regex = null)
    {
        return Validation::email($check, $deep, $regex);
    }

    public function equalTo($check, $comparedTo)
    {
        return Validation::equalTo($check, $comparedTo);
    }

    public function extension($check, $extensions = ['gif', 'jpeg', 'png', 'jpg'])
    {
        return Validation::extension($check, $extensions);
    }

    public function ip($check, $type = 'both')
    {
        return Validation::ip($check, $type);
    }

    public function minLength($check, $min)
    {
        return Validation::minLength($check, $min);
    }

    public function maxLength($check, $max)
    {
        return Validation::maxLength($check, $max);
    }

    public function money($check, $symbolPosition = 'left')
    {
        return Validation::money($check, $symbolPosition);
    }

    public function multiple($check, array $options = [], $caseInsensitive = false)
    {
        return Validation::multiple($check, $options, $caseInsensitive );
    }

    public function numeric($check)
    {
        return Validation::numeric($check);
    }

    public function naturalNumber($check, $allowZero = false)
    {
        return Validation::naturalNumber($check, $allowZero);
    }

    public function range($check, $lower = null, $upper = null)
    {
        return Validation::range($check, $lower, $upper);
    }

    public function url($check, $strict = false)
    {
        return Validation::url($check, $strict);
    }

    public function inList($check, array $list, $caseInsensitive = false)
    {
        return Validation::inList($check, $list, $caseInsensitive);
    }

    public function userDefined($check, $object, $method, $args = null)
    {
        return Validation::userDefined($check, $object, $method, $args);
    }

    public function uuid($check)
    {
        return Validation::uuid($check);
    }

    public function luhn($check, $deep = false)
    {
        return Validation::luhn($check, $deep);
    }

    public function mimeType($check, $mimeTypes = [])
    {
        return Validation::mimeType($check, $mimeTypes);
    }

    public function fileSize($check, $operator = null, $size = null)
    {
        return Validation::fileSize($check, $operator, $size);
    }

    public function uploadError($check, $allowNoFile = false)
    {
        return Validation::uploadError($check, $allowNoFile);
    }

    public function uploadedFile($file, array $options = [])
    {
        return Validation::uploadedFile($file, $options);
    }

}
