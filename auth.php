<?php


 /*
 * 
 * PHP version 5
 * 
 * @category   JFusion
 * @package    JFusionPlugins
 * @subpackage PhpVms
 * @author     JFusion Team <webmaster@jfusion.org>
 * @copyright  2011 JFusion. All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link       http://www.jfusion.org
 */
 
// no direct access
defined('_JEXEC') or die('Restricted access');


/**
 * JFusion Authentication Class for PhpVms
 * For detailed descriptions on these functions please check the model.abstractauth.php
 * 
 * @category   JFusion
 * @package    JFusionPlugins
 * @subpackage PhpVms
 * @author     JFusion Team <webmaster@jfusion.org>
 * @copyright  2011 JFusion. All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link       http://www.jfusion.org
 */
class JFusionAuth_phpvms extends JFusionAuth 
{
    /**
     * returns the name of this JFusion plugin
     * @return string name of current JFusion plugin
     */
    function getJname() 
    {
        return 'phpvms';
    }
     function generateEncryptedPassword($userinfo) {
	/*
        $params = JFusionFactory::getParams($this->getJname());
        $salt = md5(date('His'));
		$the_crypt = md5($userinfo->password_clear . $salt);
        return $the_crypt;*/
    }
}