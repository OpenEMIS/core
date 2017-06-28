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

    public function toArray()
    {
        $array = [
            'title' => $this->getTitle(),
            'url' => $this->getUrl(),
            'active' => $this->getActive()
        ];
        return $array;
    }
}
