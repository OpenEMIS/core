<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Validation;

/**
 * Describes objects that can be validated by passing a Validator object.
 */
interface ValidatableInterface
{

    /**
     * Validates the internal properties using a validator object and returns any
     * validation errors found.
     *
     * @param \Cake\Validation\Validator $validator The validator to use when validating the entity.
     * @return array
     */
    public function validate(Validator $validator);
}
