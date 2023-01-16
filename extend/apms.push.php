<?php
if (!defined('_GNUBOARD_')) exit;

// mysql password length 41, old_password 의 경우에는 16
if(!defined('G5_MYSQL_PASSWORD_LENGTH')) {
	define('G5_MYSQL_PASSWORD_LENGTH', 41);
}

?>