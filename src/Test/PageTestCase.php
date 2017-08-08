<?php
namespace App\Test;

use Cake\TestSuite\IntegrationTestCase;
use Cake\Utility\Hash;
use Cake\Utility\Security;

use App\Test\AppTestCase;
use Page\Traits\EncodingTrait;

class PageTestCase extends AppTestCase
{
    use EncodingTrait;
}