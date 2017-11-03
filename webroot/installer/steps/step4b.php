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
        $schoolName = $_POST['school_name'];
        $schoolCode = $_POST['school_code'];

        if (!empty($acctPass1) && $acctPass1 === $acctPass2) {
            if (!empty($schoolName)) {
                try {
                    // $pdo = new PDO($connectionString, $user, $pass);
                    // createUser($pdo, $acctUser, $acctPass1);
                    // createSchool($pdo, $schoolName, $schoolCode);
                    $_SESSION['username'] = $acctUser;
                    $_SESSION['password'] = $acctPass1;
                    header('Location: ' . $url . '?step=5');
                } catch (PDOException $ex) {
                    $_SESSION['error'] = $ex->getMessage();
                    header('Location: ' . $url . '?step=4');
                }
            } else {
                $_SESSION['error'] = 'Please enter your school name.';
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

function createUser($pdo, $username, $password)
{
    $truncate = "TRUNCATE TABLE `security_users`; TRUNCATE TABLE `security_user_types`;";
    $insertSQL = "INSERT INTO `security_users` (id, identification_no, country_id, username, password, first_name, middle_name, last_name, gender, super_admin, status, created_user_id, created) VALUES (%s)";

    $values = array(
        1, // id,
        "'" . 'S123' . "'", // identification_no
        1, // country_id
        "'" . $username . "'",
        "'" . password($password) . "'",
        "'System'",         // first_name
        "''",               // middle_name
        "'Administrator'",  // last_name
        "'M'",              // gender -> default to M
        1,                  // 1 = super admin
        1,                  // 1 = active
        1,                  // created by
        'NOW()'
    );
    $insertTypeSQL = "INSERT INTO `security_user_types` (id, security_user_id, type) VALUES (1, 1, 1);";
    $pdo->exec($truncate);
    $pdo->exec(sprintf($insertSQL, implode(', ', $values)));
    $pdo->exec($insertTypeSQL);
}

function createSchool($pdo, $schoolName, $schoolCode)
{
    $truncate = "TRUNCATE TABLE `institution_sites`;";
    $insertSQL = "INSERT INTO `institution_sites` (id, name, code, address, postal_code, date_opened, created_user_id, created) VALUES (%s)";
    $values = array(
        1, // id
        "'" . $schoolName . "'",
        "'" . $schoolCode . "'",
        "''",                       // address
        "''",                       // postal_code
        "'" . date('Y-m-d') . "'",  // date_opened
        1,                          // created by
        'NOW()'
    );
    $pdo->exec($truncate);
    $pdo->exec(sprintf($insertSQL, implode(', ', $values)));
}
