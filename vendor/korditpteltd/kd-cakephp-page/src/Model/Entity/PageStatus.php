<?php
namespace Page\Model\Entity;

use ArrayObject;

use Cake\Utility\Inflector;
use Cake\Log\Log;

class PageStatus
{
    private $code;
    private $type;
    private $error;
    private $message;

    const SUCCESS = 200;
    const BAD_REQUEST = 400;
    const UNAUTHORISED = 401;
    const RECORD_NOT_FOUND = 404;
    const UNSUPPORTED_MEDIA_TYPE = 415;
    const VALIDATION_ERROR = 422;
    const UNEXPECTED_ERROR = 500;

    public function __construct()
    {
        $this->code = self::SUCCESS;
        $this->type = 'success';
        $this->error = false;
        $this->message = 'Success';
    }

    public function getCode()
    {
        return $this->code;
    }

    public function setCode($code)
    {
        $this->code = $code;
        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    public function getError()
    {
        return $this->error;
    }

    public function setError($error)
    {
        $this->error = $error ? true : false;
        return $this;
    }

    public function getMessage()
    {
        return __($this->message);
    }

    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }

    public function toArray()
    {
        return [
            'code' => $this->code,
            'type' => $this->type,
            'error' => $this->error,
            'message' => $this->message
        ];
    }
}
