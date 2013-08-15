<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
* @name CI Smarty
* @copyright Dwayne Charrington, 2011.
* @author Dwayne Charrington and other Github contributors
* @license (DWYWALAYAM)
           Do What You Want As Long As You Attribute Me Licence
* @version 1.2
* @link http://ilikekillnerds.com
*/

// Your views directory with a trailing slash
$config['smarty']['template_dir']           = APPPATH."views/";

// Where templates are compiled
$config['smarty']['compile_dir']            = BASEPATH."cache";

// Where templates are cached
$config['smarty']['cache_dir']              = BASEPATH."cache";

// Where Smarty configs are located
//$config['config_directory']     = APPPATH."third_party/Smarty/configs";
$config['smarty']['config_dir']             = BASEPATH."libraries/Smarty/configs";


// Default extension of templates if one isn't supplied
$config['smarty']['template_ext']           = 'htm';

// PHP error reporting level (can be any valid error reporting level)
$config['smarty']['error_reporting']        = "E_ALL";

$config['smarty']['security']   		    = TRUE;
$config['smarty']['php_handling']   	    = Smarty::PHP_ALLOW;
$config['smarty']['php_functions']  	    = array();
$config['smarty']['php_functions']  	    = TRUE;
$config['smarty']['php_modifiers']   	    = array();
$config['smarty']['streams']   		        = array();
$config['smarty']['allow_php_tag']   	    = TRUE;
$config['smarty']['force_compile']   	    = TRUE;
