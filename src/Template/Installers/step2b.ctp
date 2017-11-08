<?php
/*
OpenEMIS School
Open School Management Information System

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by the Free Software Foundation,
either version 3 of the License, or any later version. This program is distributed in the hope
that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details. You should
have received a copy of the GNU General Public License along with this program.  If not, see
<http://www.gnu.org/licenses/>.  For more information please email contact@openemis.org.
*/

session_start();

if(!empty($_POST) && isset($_POST['database'])) {

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
		$this->response->header('Location: step3');

	} catch (PDOException $ex) {
		$code = $ex->getCode();
		if($code == 1045) { // access denied
			$_SESSION['error'] = 'You have entered a wrong username or password';
		} else if($code == 2002) { // invalid host
			$_SESSION['error'] = 'You have entered an invalid database host. <br />(You may try 127.0.0.1 if localhost doesn\'t work.)';
		} else {
			$_SESSION['error'] = $ex->getMessage();
		}
		$this->response->header('Location: step2');
	}
} else {
	$this->response->header('Location: step2');
}
?>