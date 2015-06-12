<?php

namespace App\Test\TestCase\View\Helper;

use App\View\Helper\BensonHelper;
use Cake\TestSuite\TestCase;
use Cake\View\View;

class BensonHelperTest extends TestCase {
	public function setUp() {
		parent::setUp();
		$view = new View();
		$this->Benson = new BensonHelper($view);
	}

	public function testNav() {
		$result = $this->Benson->nav();
		$this->assertContains('Students', $result);
	}
}
