<?php

/* Start Session */

session_name("qwench");
session_start();

/* Define */

define('ROOT',DIRNAME(__FILE__));
define('DS',DIRECTORY_SEPARATOR);

/* Get Basic Details */
$path = explode("/", substr(@$_SERVER['PATH_INFO'],1));

$controller = 'questions';
$action = 'index';
if (empty($_GET['type'])) { $_GET['type'] = "active"; }
$norender = false;
$noheader = false;

if (!empty($path[0])) { $controller = $path[0]; if ($_GET['type'] == "active") { $_GET['type'] = ""; } }
if (!empty($path[1])) { $action = $path[1]; if ($_GET['type'] == "active") { $_GET['type'] = ""; } }

/* Include Libraries */

include_once 'config.php';
include_once 'libraries/template.class.php';
include_once 'libraries/helper.class.php';

$template = new Template($controller,$action);
$helper = new Helper();

include_once 'libraries/shared.php';
include_once 'libraries/markdown.php';
include_once 'libraries/timeago.php';
include_once 'libraries/score.php';
include_once 'libraries/pagination.class.php';
include_once 'controllers/helpers.php';


/* Check Debug vs Production Mode */
if (DEBUG_MODE == '1')
{
	error_reporting(E_ALL);
	ini_set('display_errors','On');
}





/* Basic Bootstrapping */

include 'controllers/'.$controller.'.php';
if (function_exists($action)) {
	call_user_func($action);
} else {
	call_user_func('index');
}
if ($norender == false) {
	$template->render($noheader);
}
