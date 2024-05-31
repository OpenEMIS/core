<?php
namespace Page\Model\Entity;

use ArrayObject;

use Cake\Routing\Router;
use Cake\Log\Log;

class PageTab
{
    private $title;
    private $url;
    private $active;
    private $attributes = [];

    public function __construct()
    {
        $this->title = 'No Name';
        $this->active = false;
        $this->url = [];
    }

    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function setActive($active)
    {
        $this->active = $active;
        return $this;
    }

    public function getActive()
    {
        return $this->active;
    }

    public function setAttributes($key, $value)
    {
        $this->attributes[$key] = $value;
        return $this;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function toArray()
    {
        $array = [
            'title' => $this->getTitle(),
            'url' => $this->getUrl(),
            'active' => $this->getActive(),
            'attributes' => $this->getAttributes()
        ];
        return $array;
    }
}
