<?php
namespace Archive\Model\Entity;

use Cake\ORM\Entity;

/**
 * Connection Entity
 *
 * @property int $id
 * @property string $name
 * @property string $db_type_id
 * @property int $host
 * @property string $host_port
 * @property string $db_name
 * @property string $username
 * @property string $password
 * @property int $conn_status_id
 * @property \Cake\I18n\Time $status_checked
 * @property int $modified_user_id
 * @property \Cake\I18n\Time $modified
 * @property int $created_user_id
 * @property \Cake\I18n\Time $created
 */class ArchiveConnection extends Entity
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
        '*' => true,
        'id' => false
    ];

    /**
     * Fields that are excluded from JSON versions of the entity.
     *
     * @var array
     */
    /*protected $_hidden = [
        'password'
    ];*/
}
