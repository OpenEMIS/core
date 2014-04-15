<?php
/**
 * Tree behavior class.
 *
 * Enables a model object to act as a node-based tree.
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Fixture
 * @since         CakePHP(tm) v 1.2.0.5331
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Class NumberTreeTwoFixture
 *
 * Generates a tree of data for use testing the tree behavior
 *
 * @package       Cake.Test.Fixture
 */
class NumberTreeTwoFixture extends CakeTestFixture {

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id'	=> array('type' => 'integer', 'key' => 'primary'),
		'name'	=> array('type' => 'string', 'null' => false),
		'number_tree_id' => array('type' => 'integer', 'null' => false),
		'parent_id' => 'integer',
		'lft'	=> array('type' => 'integer', 'null' => false),
		'rght'	=> array('type' => 'integer', 'null' => false)
	);
}
