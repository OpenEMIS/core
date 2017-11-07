<?php
/*
@OPENEMIS SCHOOL LICENSE LAST UPDATED ON 2014-01-30

OpenEMIS School
Open School Management Information System

Copyright © 2014 KORD IT. This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by the Free Software Foundation,
either version 3 of the License, or any later version. This program is distributed in the hope
that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details. You should
have received a copy of the GNU General Public License along with this program.  If not, see
<http://www.gnu.org/licenses/>.  For more information please email contact@openemis.org.
*/

include_once "../config.php";
require DIR_ROOT . 'vendor/autoload.php';
include DIR_ROOT . 'config/bootstrap.php';

use Cake\ORM\TableRegistry;
use Cake\I18n\Date;

session_start();
$url = getRoot() . '/installer/';

if (isset($_SESSION['db_host']) && isset($_SESSION['db_user']) && isset($_SESSION['db_pass']) && isset($_SESSION['db_name'])) {
    $host = $_SESSION['db_host'];
    $port = $_SESSION['db_port'];
    $user = $_SESSION['db_user'];
    $pass = $_SESSION['db_pass'];
    $db = $_SESSION['db_name'];

    $connectionString = sprintf('mysql:host=%s;port=%d;dbname=%s', $host, $port, $db);

    if (!empty($_POST) && isset($_POST['createUser'])) {
        $acctUser = $_POST['username'];
        $acctPass1 = $_POST['password1'];
        $acctPass2 = $_POST['password2'];

        if (!empty($acctPass1) && $acctPass1 === $acctPass2) {
            try {
                $pdo = new PDO($connectionString, $user, $pass);
                createUser($acctUser, $acctPass1);
                $_SESSION['username'] = $acctUser;
                $_SESSION['password'] = $acctPass1;
                header('Location: ' . $url . '?step=5');
            } catch (PDOException $ex) {
                $_SESSION['error'] = $ex->getMessage();
                header('Location: ' . $url . '?step=4');
            }
        } else {
            $_SESSION['error'] = 'Your account passwords do not match.';
            header('Location: ' . $url . '?step=4');
        }
    } else {
        $_SESSION['error'] = 'Please enter the account info.';
        header('Location: ' . $url . '?step=4');
    }
} else {
    header('Location: ' . $url . '?step=4');
}

function createUser($username, $password)
{
    $UserTable = TableRegistry::get('User.Users');
    $data = [
        'id' => 1,
        'username' => $username,
        'password' => $password,
        'openemis_no' => 'sysadmin',
        'first_name' => 'System',
        'middle_name' => null,
        'third_name' => null,
        'last_name' => 'Administrator',
        'preferred_name' => null,
        'email' => null,
        'address' => null,
        'postal_code' => null,
        'address_area_id' => null,
        'birthplace_area_id' => null,
        'gender_id' => 1,
        'date_of_birth' => new Date(),
        'date_of_death' => null,
        'nationality_id' => null,
        'identity_type_id' => null,
        'identity_number' => null,
        'external_reference' => null,
        'super_admin' => 1,
        'status' => 1,
        'last_login' => null,
        'photo_name' => null,
        'photo_content' => null,
        'preferred_language' => 'en',
        'is_student' => 0,
        'is_staff' => 0,
        'is_guardian' => 0,
    ];

    $entity = $UserTable->newEntity($data);
    $UserTable->save($entity);
}
