<?php


/**
 * JFusion User Class for PhpVms
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
 * JFusion User Class for PhpVms
 * For detailed descriptions on these functions please check the model.abstractuser.php
 * 
 * @category   JFusion
 * @package    JFusionPlugins
 * @subpackage PhpVms
 * @author     JFusion Team <webmaster@jfusion.org>
 * @copyright  2011 JFusion. All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link       http://www.jfusion.org
 */
class JFusionUser_phpvms extends JFusionUser {
    function &getUser($userinfo) {
	    //get the identifier
        $identifier = $userinfo;
        if (is_object($userinfo)) {
            $identifier = $userinfo->email;
        }
        // Get user info from database
		$db = JFusionFactory::getDatabase($this->getJname());
        $params = JFusionFactory::getParams($this->getJname());
		$tbp = $params->get('database_prefix');
        $query = "SELECT pilotid, firstname, lastname, email, code, location, hub, password, salt, bgimage, lastlogin, totalflights, totalhours, totalpay,
         transferhours, rankid, rank, ranklevel, confirmed, retired, joindate, lastpirep, lastip FROM " . $tbp . "pilots WHERE email =" . $db->Quote($identifier);
        $db->setQuery($query);
        $result = $db->loadObject();
        // read through params for cookie key (the salt used)
        return $result;
    }

    /**
     * returns the name of this JFusion plugin
     * @return string name of current JFusion plugin
     */    
    function getJname() 
    {
        return 'phpvms';
    }
    function deleteUser($userinfo) {
        /* Warning: this function mimics the original phpvms function which is a suggestive deletion, 
		all user information remains in the table for past reference purposes. To delete everything associated
		with an account and an account itself, you will have to manually delete them from the table yourself. */
		// get the identifier
        $identifier = $userinfo;
        if (is_object($userinfo)) {
            $identifier = $userinfo->id_customer;
        }
		$params = JFusionFactory::getParams($this->getJname());
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'UPDATE #__customer SET deleted ="1" WHERE id_customer =' . $db->Quote($identifier);
        $db->setQuery($query);
		$status["debug"][] = "Deleted user";
		return $status;
    }
    function destroySession($userinfo = "", $option = "") {
	    $status = array();
        $status['error'] = array();
        $status['debug'] = array();
	    // use phpvms cookie class and functions to delete cookie (logout function in Auth class)
		$params = JFusionFactory::getParams($this->getJname());
		$tbp = $params->get('database_prefix');
		//require($params->get('source_path') . DS . "core/codon.config.php");
		/*$query="SELECT pilotid FROM " . $tbp . "pilots WHERE email = '" . $userinfo->email . "'";
        $db->setQuery($query);
		$pilotid = $db->loadResult();
		$query="DELETE FROM ".$tbp."sessions WHERE pilotid = '" . $pilotid . "'";
		$status["error"][] = "Random debugging text";
	    if(!$db->setQuery($query))
		{
		 $status["error"][] = "Error Could not delete session, doesn't exist";
		}
		else
		{
		 $status["debug"][] = "Deleted session and session data";
		}*/
		#self::remove_sessions(SessionManager::GetValue('userinfo', 'pilotid'));
		
		# Mark them as guest
		/*Auth::update_session(Auth::$session_id, 0);
		
		# "Ghost" entry
		//self::start_session(self::$userinfo->pilotid); // Orphaned?

		SessionManager::Set('loggedin', false);
		SessionManager::Set('userinfo', '');
		SessionManager::Set('usergroups', '');

		# Delete cookie
		
		$_COOKIE['VMSAUTH'] = '';
		setcookie("VMSAUTH", false, time() - 3600*24*30, "/");

		Auth::$loggedin = false;*/
    }
    function createSession($userinfo, $options, $framework = true) {
	    $params = JFusionFactory::getParams($this->getJname());
	    $status = array();
        $status['error'] = array();
        $status['debug'] = array();
        // this uses a code extract from authentication.php that deals with logging in completely
		$db = JFusionFactory::getDatabase($this->getJname());
		require($params->get('source_path') . DS . "core/codon.config.php");
		$passwd = $userinfo->password_clear;
	    $email = $userinfo->email;
		$passwd = trim($passwd);
		$email = trim($email);

		# Allow them to login in any manner:
		#  Email: blah@blah.com
		#  Pilot ID: VMA0001, VMA 001, etc
		#  Just ID: 001

		# They're logging in with an email
		/*if(preg_match('/^.*\@.*$/i', $email) > 0)
		{
			$emailaddress = DB::escape($email);
			$sql = 'SELECT * FROM ' . $tbp . 'pilots
					WHERE email=\''.$email.'\'';
		}*/ 
		
		/*if(!$users_info)
		{
			Auth::$error_message = 'This user does not exist';
			return false;
		}*/
		
		/*if($users_info->retired == 1)
		{
			Auth::$error_message = 'Your account was deactivated, please contact an admin';
			return false;
		}*/

		//ok now check it
		$hash = md5($passwd . $userinfo->salt);
		
		if($hash == $userinfo->password)
		{	
			Auth::$userinfo = $userinfo;
			
			Auth::update_session(Auth::$session_id, Auth::$userinfo->pilotid);

			SessionManager::Set('loggedin', 'true');	
			SessionManager::Set('userinfo', $userinfo);
			SessionManager::Set('usergroups', PilotGroups::GetUserGroups($userinfo->pilotid));
			
			PilotData::updateProfile($userinfo->pilotid, array(
				'lastlogin'=>'NOW()', 
				'lastip' => $_SERVER['REMOTE_ADDR'],
				)
			);
			
			return true;
		}			
		else 
		{
			Auth::$error_message = 'Invalid login, please check your username and password';
			Auth::LogOut();
			
			return false;
		}
	}
    function filterUsername($username) {
        return $username;
    }
    function updatePassword($userinfo, &$existinguser, &$status) {
        jimport('joomla.user.helper');
        $existinguser->password_salt = JUserHelper::genRandomPassword(8);
        $existinguser->password = md5($userinfo->password_clear . $existinguser->password_salt);
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'UPDATE #__customer SET password =' . $db->Quote($existinguser->password) . ', salt = ' . $db->Quote($existinguser->password_salt) . ' WHERE id_customer =' . (int)$existinguser->userid;
        $db->setQuery($query);
        if (!$db->query()) {
            $status['error'][] = JText::_('PASSWORD_UPDATE_ERROR') . $db->stderr();
        } else {
            $status['debug'][] = JText::_('PASSWORD_UPDATE') . ' ' . substr($existinguser->password, 0, 6) . '********';
        }
    }
    function createUser($userinfo, &$status) {
		$db = JFusionFactory::getDatabase($this->getJname());
	    $params = JFusionFactory::getParams($this->getJname());
		
		/* split full name into first and with/or without middlename, and lastname */
		$users_name = $userinfo->name;
		list($uf_name, $ul_name) = preg_split('/\s+(?=[^\s]+$)/', $users_name, 2);
		if (is_null($ul_name)) // meaning only two names were entered
		{
			$end_name = $um_name;
		}
		else
		{
			$end_name = explode(' ', $ul_name);
			$size = sizeof($ul_name);
			$end_name = $ul_name[$size-1];
		}
		// now have first name as $uf_name, and last name as $end_name
		
		/* user variables submitted through form (emulated) */
	    $user_variables = array(
	    'first_name' => $uf_name,
		'last_name' => $end_name,
		'email_address' => $userinfo->email,
		'airline' => "", // custom variable for registration
		'hub' => "", // custom variable for registration
		'location' => "", // custom variable for registration
		'password' => $userinfo->password_clear,
		'recaptcha' => "" // custom variable for registration
	    );
		
		/* array to go into table phpvms_pilots */
	    $phpvms_pilots = array(
	    'pilotid' => "", // numeric character between 1 and 11 characters long
 		'firstname' => $user_variables['first_name'], // alphanumeric values between 6 and 25 characters long
 		'lastname' => $user_variables['last_name'], // alphanumeric values between 6 and 25 characters long
 		'email' => $user_variables['email_address'], // alphanumeric values between 6 and 100 characters long
 		'code' => "AAA", // letters 3 characters long
 		'location' => "US", // alphanumeric values between 6 and 32 characters long
 		'hub' => "", // alphanumeric value 4 characters long
 		'password' => "", /* PLACEHOLDER WITH VALUE ANNOUNED LATER */
 		'salt' => md5(date('His')), // alphanumeric values between 6 and 32 characters long
 		'bgimage' => "", // alphanumeric values between 6 and 30 characters long
 		'lastlogin' => "0000-00-00", // date in YYYY-MM-DD format
 		'totalflights' => 0, // numeric value between 1 and 11 characters long
 		'totalhours' => 0, // binary
 		'totalpay' => 0, // binary
 		'transferhours' => 0, // binary
 		'rankid' => 1, // numeric value between 1 and 11 characters long
 		'rank' => "", // alphanumeric values between 6 and 32 characters long
 		'ranklevel' => 1, // numeric value between 1 and 11 characters long
 		'confirmed' => 1, // numeric value 5 characters long
 		'retired' => 0,	// numeric value 6 characters long
 		'joindate' => date("Y-m-d h:m:s"), // date and time in YYYY-MM-DD HH:MM:SS format
 		'lastpirep' => date("Y-m-d h:m:s"), // date and time in YYYY-MM-DD HH:MM:SS format
 		'lastip' => $_SERVER['REMOTE_ADDR'] // alphanumeric values between 6 and 25 characters long
		);
		
		/* DATABASE ORDER CHANGED IN PASSWORD AND SALT VARIABLES TO AVOID MENTIONING ARRAY ITEM BEFORE DECLARED */
		$phpvms_pilots['password'] = md5($user_variables['password'].$phpvms_pilots['salt']); // alphanumeric values between 6 and 32 characters long
		
		/* array to go into table phpvms_sessions */
	    $phpvms_sessions = array(
	    'id' => "",	// numeric character between 1 and 11 characters long
	    'pilotid' => "", // numeric character between 1 and 11 characters long
 		'ipaddress' => $_SERVER['REMOTE_ADDR'], // alphanumeric values between 6 and 25 characters long
 		'logintime' => "0000-00-00 00:00:00" // date and time in YYYY-MM-DD HH:MM:SS format
		);
		
		/* safe data check and validation of array $user_variables
	    no other unique variables are used so this check only includes these */
	
	    // Validate gender
	    /*if (!Validate::isGenderIsoCode($user_variables['id_gender'])){
		    $errors[] = Tools::displayError('gender not valid');
		    unset($phpvms_pilots);
	    }*/
	
        // Validate first name
	    /*if (!Validate::isName($user_variables['firstname'])){
	        $errors[] = Tools::displayError('first name wrong');
	        unset($phpvms_pilots);
	    }*/
	 
	    // Validate second name
	    /*if (!Validate::isName($user_variables['lastname'])){
	        $errors[] = Tools::displayError('second name wrong');
	        unset($phpvms_pilots);
	    }*/
	 
	    // Validate address first name
	    /*if (!Validate::isName($user_variables['customer_firstname'])){
	        $errors[] = Tools::displayError('customer first name wrong');
	        unset($ps_address);
	    }*/
	 
	    // Validate address last name
	    /*if (!Validate::isName($user_variables['customer_lastname'])){
	        $errors[] = Tools::displayError('customer second name wrong');
	        unset($ps_address);
	    }/*
	
	    // Validate email
	    /*if (!Validate::isEmail($user_variables['email'])){
	        $errors[] = Tools::displayError('e-mail not valid');
	        unset($phpvms_pilots);
	    }*/
	 
	    // Validate password
	    /*if (!Validate::isPasswd($user_variables['passwd'])){
	        $errors[] = Tools::displayError('invalid password');
	        unset($phpvms_pilots);
	    }/*
	
	    // Validate date of birth 
	    /*if (!@checkdate($user_variables['months'], $user_variables['days'], $user_variables['years']) AND !( $user_variables['months']== '' AND $user_variables['days'] == '' AND $user_variables['years'] == '')){
		    $errors[] = Tools::displayError('invalid birthday');
		    unset($phpvms_pilots);
	    }*/
	 
	    // Validate newsletter checkbox
        /*if (!Validate::isBool($user_variables['newsletter'])){
	        $errors[] = Tools::displayError('newsletter invalid choice');
	        unset($phpvms_pilots);
	    }/*
	 
	    // Validate special offers from partners checkbox
	    /*if (!Validate::isBool($user_variables['optin'])){
	        $errors[] = Tools::displayError('optin invalid choice');
	        unset($phpvms_pilots);
	    }*/
	 
	    // Validate company/orginization
	    /*if (!Validate::isGenericName($user_variables['company'])){
	        $errors[] = Tools::displayError('company name wrong');
	        unset($ps_address);
	    }*/
	 
	    // Do not validate address line 1 since a placeholder is been curently used
	    /*if (!Validate::isAddress($user_variables['address1'])){
	        $errors[] = Tools::displayError('address wrong');
	        unset($ps_address);
	    }*/
	 
	    // Validate address line 2
	    /*if (!Validate::isAddress($user_variables['address2'])){
	        $errors[] = Tools::displayError('address 2nd wrong');
	        unset($ps_address);
	    }*/

	    // Do not validate postcode since a placeholder is been curently used
	    /*if (!Validate::isPostCode($user_variables['postcode'])){
	        $errors[] = Tools::displayError('postcode wrong');
	        unset($ps_address);
	    }*/
	 
	    // Validate phone number
	    /*if (!Validate::isPhoneNumber($user_variables['phone'])){
	        $errors[] = Tools::displayError('invalid phone');
	        unset($ps_address);
	    }*/
	 
	    // Validate mobile number
	    /*if (!Validate::isPhoneNumber($user_variables['phone_mobile'])){
	        $errors[] = Tools::displayError('invalid mobile');
	        unset($ps_address);
	    }*/
	
	    // Do not validate village/town/city since a placeholder is been curently used
	    /*if (!Validate::isCityName($user_variables['city'])){
	        $errors[] = Tools::displayError('invalid village/town/city');
	        unset($ps_address);
	    }*/
	
	    // Validate country
	    /*if (!Validate::isInt($user_variables['id_country'])){
	        $errors[] = Tools::displayError('invalid country');
	        unset($ps_address);
        }
	    elseif (Country::getIsoById($user_variables['id_country']) === ""){
	        $errors[] = Tools::displayError('invalid country');
	        unset($ps_address);
	    }*/
	
	    // Validate state
	    /*if (!Validate::isInt($user_variables['id_state'])){
	        $errors[] = Tools::displayError('invalid state');
	        unset($ps_address);
        }
	    elseif (!State::getNameById($user_variables['id_state'])){
	        if($user_variables['id_state'] === "0"){
	            /* state valid to apply for none state *//*
	        }
	        else{
	            $errors[] = Tools::displayError('invalid state');
	            unset($ps_address);
	        }
	    }*/
	
	    // Validate DNI
	    /*$validateDni = Validate::isDni($user_variables['dni']);
	    if ($user_variables['dni'] != NULL AND $validateDni != 1){
		    $error = array(
		    0 => Tools::displayError('DNI isn\'t valid'),
		    -1 => Tools::displayError('this DNI has been already used'),
		    -2 => Tools::displayError('NIF isn\'t valid'),
		    -3 => Tools::displayError('CIF isn\'t valid'),
		    -4 => Tools::displayError('NIE isn\'t valid')
		    );
		    $errors[] = $error[$validateDni];
		    unset($phpvms_pilots);
	    }*/
	
	    // Validate alias
	    /*elseif (!Validate::isMessage($user_variables['alias'])){
	        $errors[] = Tools::displayError('invalid alias');
	        unset($ps_address);
	    }*/
	
        // Validate extra information 	
	    /*elseif (!Validate::isMessage($user_variables['other'])){
	        $errors[] = Tools::displayError('invalid extra information');
	        unset($ps_address);
	    }*/
	
	    /* Check if account already exists (not a validation) *//*
	    elseif (Customer::customerExists($user_variables['email'])){
	        $errors[] = Tools::displayError('someone has already registered with this e-mail address');
	        unset($phpvms_pilots);
	    }*/
		
		/* enter account into phpvms database */ // if all information is validated
	    if(isset($phpvms_pilots) && isset($phpvms_sessions))
	    {
	        $tbp = $params->get('database_prefix');
	        foreach($phpvms_pilots as $key => $value){
	            if($key == "pilotid" || $key == "code" || $key == "location" || $key == "hub" || $key == "totalflights" || $key == "totalhours"){
	                if($key == "pilotid"){
	                    $insert_sql_columns = "INSERT INTO " . $tbp . "pilots (";
                        $insert_sql_values = "VALUES ("; 
			        }
					
	                else{
	                    $insert_sql_columns .= ", " . $key;
                        $insert_sql_values .= ", '" . $value . "'"; 
					}
	            }
				
	            elseif($key == "firstname"){
	                $insert_sql_columns .= "" . $key;
                    $insert_sql_values .= "'" . $value . "'";
                }
	            else{
	                $insert_sql_columns .= ", " . $key;
                    $insert_sql_values .= ", '" . $value . "'";
                }
	        } 
			
	        $insert_sql_columns .= ")";
            $insert_sql_values .= ")";
	        $query = $insert_sql_columns . $insert_sql_values;
	        $db->setQuery($query);
			$result = $db->query();
				
	        /*foreach($phpvms_sessions as $key => $value){
	            if($key == "id_customer"){
	                $insert_sql_columns = "INSERT INTO " . $tbp . "sessions (" . $key;
                    $insert_sql_values = "VALUES ('" . $value . "'";
                }
	            else{
                    $insert_sql_columns .= ", " . $key;
                    $insert_sql_values .= ", '" . $value . "'";
                }
	        }
			
	        $insert_sql_columns .= ")";
            $insert_sql_values .= ")";
	        $query = $insert_sql_columns . $insert_sql_values;
	        $db->setQuery($query);
			$result = $db->query();*/
		}
    }
    function updateEmail($userinfo, &$existinguser, &$status) {
        //we need to update the email
		$params = JFusionFactory::getParams($this->getJname());
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'UPDATE #__customer SET email =' . $db->Quote($userinfo->email) . ' WHERE id_customer =' . (int)$existinguser->userid;
        $db->setQuery($query);
        if (!$db->query()) {
            $status['error'][] = JText::_('EMAIL_UPDATE_ERROR') . $db->stderr();
        } else {
            $status['debug'][] = JText::_('PASSWORD_UPDATE') . ': ' . $existinguser->email . ' -> ' . $userinfo->email;
        }
    }
    function activateUser($userinfo, &$existinguser, &$status) {
        /* change the “active” field of the customer in the ps_customer table to 1 */
		$params = JFusionFactory::getParams($this->getJname());
        $db = JFusionFactory::getDatabase($this->getJname());
		$tbp = $params->get('database_prefix');
        $query = "UPDATE " . $tbp . "customer SET active ='1' WHERE id_customer ='" . (int)$existinguser->userid . "'";
        $db->setQuery($query);
    }
    function inactivateUser($userinfo, &$existinguser, &$status) {
        /* change the “active” field of the customer in the ps_customer table to 0 */
		$params = JFusionFactory::getParams($this->getJname());
        $db = JFusionFactory::getDatabase($this->getJname());
		$tbp = $params->get('database_prefix');
        $query = "UPDATE " . $tbp . "customer SET active ='0' WHERE id_customer ='" . (int)$existinguser->userid . "'";
        $db->setQuery($query);
    }
}