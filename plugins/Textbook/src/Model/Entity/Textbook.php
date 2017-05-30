<?php
namespace Textbook\Model\Entity;

use Cake\ORM\Entity;

class Textbook extends Entity
{
    protected $_virtual = ['code_title'];

    protected function _getCodeTitle() {
        return $this->code . ' - ' . $this->title;
    }
}