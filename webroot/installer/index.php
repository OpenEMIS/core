<?php
include_once('config.php');
session_start();
if(file_exists(CONFIG_FILE) && !file_exists(INSTALL_FILE)) {
	header('Location: ' . getHostURL().getRoot());
} else {
	include_once('elements/header.php');
	include_once('elements/content.php');
	include_once('elements/footer.php');
}
?>