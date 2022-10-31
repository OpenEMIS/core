<?php
namespace Restful\Controller;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use ArrayObject;

interface RestfulInterface
{
    public function token();

    public function nothing();

    public function options();

    public function index();

    public function add();

    public function view($id);

    public function edit();

    public function delete();
}
