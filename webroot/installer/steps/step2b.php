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

if (!empty($_POST) && isset($_POST['database'])) {
    $host = $_POST['hostname'];
    $port = $_POST['port'];
    $user = $_POST['username'];
    $pass = $_POST['password'];

    $connectionString = sprintf('mysql:host=%s;port=%d', $host, $port);
    try {
        $link = new PDO($connectionString, $user, $pass);
        $_SESSION['db_host'] = $host;
        $_SESSION['db_port'] = $port;
        $_SESSION['db_root'] = $user;
        $_SESSION['db_root_pass'] = $pass;
        header('Location: ' . $url . '?step=3');
    } catch (PDOException $ex) {
        $code = $ex->getCode();
        if ($code == 1045) { // access denied
            $_SESSION['error'] = 'You have entered a wrong username or password';
        } else if ($code == 2002) { // invalid host
            $_SESSION['error'] = 'You have entered an invalid database host. <br />(You may try 127.0.0.1 if localhost doesn\'t work.)';
        } else {
            $_SESSION['error'] = $ex->getMessage();
        }
        header('Location: ' . $url . '?step=2');
    }
} else {
    header('Location: ' . $url . '?step=2');
}
