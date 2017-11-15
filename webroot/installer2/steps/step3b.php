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

if (!empty($_POST) && isset($_POST['createDatabase'])) {
    $host = $_SESSION['db_host'];
    $port = $_SESSION['db_port'];
    $root = $_SESSION['db_root'];
    $rootPass = $_SESSION['db_root_pass'];

    $db = $_POST['database'];
    $dbUser = $_POST['databaseLogin'];
    $dbPassword = $_POST['databasePassword1'];
    $dbConfirm = $_POST['databasePassword2'];

    $connectionString = sprintf('mysql:host=%s;port=%d', $host, $port);

    if (!empty($dbPassword) && $dbPassword === $dbConfirm) {
        try {
            $pdo = new PDO($connectionString, $root, $rootPass);
            $template = str_replace('{host}', "'$host'", CONFIG_TEMPLATE);
            $template = str_replace('{port}', "'$port'", $template);
            $template = str_replace('{user}', "'$dbUser'", $template);
            $template = str_replace('{pass}', "'$dbPassword'", $template);
            $template = str_replace('{database}', "'$db'", $template);
            $dbFileHandle = fopen(CONFIG_FILE, 'w');
            $privateKeyHandle = fopen(CONFIG_DIR . 'private.key', 'w');
            $publicKeyHandle = fopen(CONFIG_DIR . 'public.key', 'w');
            $appExtraHandle = fopen(CONFIG_DIR . 'app_extra.php', 'w');

            $pubKey = $pubKey["key"];
            if ($dbFileHandle && $privateKeyHandle && $publicKeyHandle) {
                $res = openssl_pkey_new(['private_key_bits' => 1024]);
                openssl_pkey_export($res, $privKey);
                fwrite($privateKeyHandle, $privKey);
                fclose($privateKeyHandle);
                $pubKey = openssl_pkey_get_details($res);
                fwrite($publicKeyHandle, $pubKey['key']);
                fclose($publicKeyHandle);
                fwrite($dbFileHandle, $template);
                fclose($dbFileHandle);
                fwrite($appExtraHandle, APP_EXTRA_TEMPLATE);
                createDb($pdo, $db);
                createDbUser($pdo, $host, $dbUser, $dbPassword, $db);
                $_SESSION['db_user'] = $dbUser;
                $_SESSION['db_pass'] = $dbPassword;
                $_SESSION['db_name'] = $db;
                header('Location: ' . $url . '?step=4');
            } else {
                $_SESSION['error'] = 'Unable to create configuration file. Please check your folder permissions. <br />' . CONFIG_DIR;
                header('Location: ' . $url . '?step=3');
            }
        } catch (PDOException $ex) {
            $_SESSION['error'] = $ex->getMessage();
            header('Location: ' . $url . '?step=3');
        }
    } else {
        $_SESSION['error'] = 'Your database passwords do not match.';
        header('Location: ' . $url . '?step=3');
    }
} else {
    header('Location: ' . $url . '?step=3');
}

function createDb($pdo, $db)
{
    $dbSql = "SELECT 1 FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?;";
    $dbExists = $pdo->prepare($dbSql);
    $dbExists->execute([$db]);
    $result = $dbExists->fetchAll();
    if (!$result) {
        $createDbSQL = sprintf("CREATE DATABASE %s CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", $db);
        $pdo->exec($createDbSQL);
    } else {
        throw new PDOException('Please choose another database name as this database exist.');
    }
}

function createDbUser($pdo, $host, $user, $password, $db)
{
    $userSql = "SELECT 1 FROM mysql.user WHERE User = ? AND Host = ?";
    $userExists = $pdo->prepare($userSql);
    $userExists->execute([$user, $host]);
    $result = $userExists->fetchAll();
    if (!$result) {
        $createUserSQL = sprintf("CREATE USER '%s'@'%s' IDENTIFIED BY '%s'", $user, $host, $password);
        $flushPriviledges = "FLUSH PRIVILEGES";
        $grantSQL = sprintf("GRANT ALL ON %s.* TO '%s'@'%s'", $db, $user, $host);
        $pdo->exec($createUserSQL);
        $pdo->exec($grantSQL);
        $pdo->exec($flushPriviledges);
    } else {
        throw new PDOException('Please choose another Username as this user already exist.');
    }
}
