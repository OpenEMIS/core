<?php
namespace OAuth\Model\Entity;

use Cake\ORM\Entity;

/**
 * Credential Entity
 *
 * @property int $id
 * @property string $name
 * @property string $client_id
 * @property string $public_key
 * @property int $modified_user_id
 * @property \Cake\I18n\Time $modified
 * @property int $created_user_id
 * @property \Cake\I18n\Time $created
 *
 * @property \OAuth\Model\Entity\Client $client
 * @property \OAuth\Model\Entity\ModifiedUser $modified_user
 * @property \OAuth\Model\Entity\CreatedUser $created_user
 */
class Credential extends Entity
{

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected $_accessible = [
        '*' => true
    ];
}
