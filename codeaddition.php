<?php
define( '_JEXEC', 1 );
define( 'DS', DIRECTORY_SEPARATOR );
define('JPATH_BASE', dirname(__FILE__) );

require_once ( JPATH_BASE .DS.'includes'.DS.'defines.php' );
require_once ( JPATH_BASE .DS.'includes'.DS.'framework.php' );

$mainframe =& JFactory::getApplication('site');
$mainframe->initialise();

$session =& JFactory::getSession();
$user = $session->get( 'user' );
?>