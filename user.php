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
		'email_address' => $userinfo->email
		);
		
		// set as true to enable jfuzed registration (extra form fields)
		$jfuzed_registration = "true";
		if($jfuzed_registration === "true"){
			$user_variables['airline'] = $userinfo->airline; // custom variable for registration
			$user_variables['hub'] = $userinfo->hub; // custom variable for registration
			$user_variables['location'] = $userinfo->location; // custom variable for registration
	   		 
	   		$db = JFusionFactory::getDatabase($this->getJname());
        	$params = JFusionFactory::getParams($this->getJname());
			$tbp = $params->get('database_prefix');
	   		// get list of airline codes and compare against
	   		$query = "SELECT * FROM ".$tbp."airlines";
			$db->setQuery($query);
			$result = $db->loadRowList();
			foreach($result as $row){
				if($user_variables['airline'] == $row['0']){
					$user_variables['airline'] = $row['1'];
				}
			}
	   		 // compare against list of locations (case per location)
	   		 switch($user_variables['location']){
  				case 0:
  				$user_variables['location'] = "AF"; // Afghanistan
  				break;
  				
  				case 1:
  				$user_variables['location'] = "AL"; // Albania
  				break;
  				
  				case 2:
  				$user_variables['location'] = "DZ"; // Algeria
  				break;
  
  				case 3:
  				$user_variables['location'] = "AS"; // American Samoa
 				break;
  
  				case 4:
  				$user_variables['location'] = "AD"; // Andorra
  				break;
  
  				case 5:
  				$user_variables['location'] = "AO"; // Angola
  				break;
  
  				case 6:
  				$user_variables['location'] = "AI"; // Anguilla
  				break;
  
  				case 7:
  				$user_variables['location'] = "AQ"; // Antarctica
  				break;
  
  				case 8:
  				$user_variables['location'] = "AG"; // Antigua And Barbuda
  				break;
  
  				case 9:
  				$user_variables['location'] = "AR"; // Argentina
  				break;
  
  				case 10:
  				$user_variables['location'] = "AM"; // Armenia
  				break;
  
  				case 11:
  				$user_variables['location'] = "AW"; // Aruba
  				break;
  
  				case 12:
  				$user_variables['location'] = "AU"; // Australia
  				break;
  
  				case 13:
  				$user_variables['location'] = "AT"; // Austria
  				break;
  
  				case 14:
  				$user_variables['location'] = "AZ"; // Azerbaijan
  				break;
  
  				case 15:
  				$user_variables['location'] = "BS"; // Bahamas
  				break;
  
  				case 16:
 				$user_variables['location'] = "BH"; // Bahrain
  				break;
  
  				case 17:
  				$user_variables['location'] = "BD"; // Bangladesh
  				break;
  
  				case 18:
  				$user_variables['location'] = "BB"; // Barbados
  				break;
  
  				case 19:
  				$user_variables['location'] = "BY"; // Belarus
  				break;
  
  				case 20:
  				$user_variables['location'] = "BE"; // Belgium
  				break;
  
  				case 21:
  				$user_variables['location'] = "BZ"; // Belize
  				break;
  
  				case 22:
  				$user_variables['location'] = "BJ"; // Benin
  				break;
  
  				case 23:
  				$user_variables['location'] = "BM"; // Bermuda
  				break;
  
  				case 24:
  				$user_variables['location'] = "BT"; // Bhutan
  				break;
  
  				case 25:
  				$user_variables['location'] = "BO"; // Bolivia
  				break;
  
  				case 26:
  				$user_variables['location'] = "BA"; // Bosnia And Herzegovina
  				break;
  
  				case 27:
  				$user_variables['location'] = "BW"; // Botswana
  				break;
  
  				case 28:
  				$user_variables['location'] = "BV"; // Bouvet Island
  				break;
  
  				case 29:
  				$user_variables['location'] = "BR"; // Brazil
  				break;
  
  				case 30:
  				$user_variables['location'] = "IO"; // British Indian Ocean Territory
  				break;
  
  				case 31:
  				$user_variables['location'] = "BN"; // Brunei Darussalam
  				break;
  
  				case 32:
  				$user_variables['location'] = "BG"; // Bulgaria
  				break;
  
  				case 33:
  				$user_variables['location'] = "BF"; // Burkina Faso
  				break;
  
  				case 34:
  				$user_variables['location'] = "BI"; // Burundi
  				break;
  
  				case 35:
  				$user_variables['location'] = "KH"; // Cambodia
  				break;
  
  				case 36:
  				$user_variables['location'] = "CM"; // Cameroon
  				break;
  
  				case 37:
  				$user_variables['location'] = "CA"; // Canada
  				break;
  
  				case 38:
  				$user_variables['location'] = "Catalonia"; // Catalonia
  				break;
  
  				case 39:
  				$user_variables['location'] = "CV"; // Cape Verde
  				break;
  
  				case 40:
 				$user_variables['location'] = "KY"; // Cayman Islands
  				break;
  
  				case 41:
  				$user_variables['location'] = "CF"; // Central African Republic
  				break;
  
  				case 42:
  				$user_variables['location'] = "TD"; // Chad
  				break;
  
  				case 43:
  				$user_variables['location'] = "CL"; // Chile
  				break;
  
  				case 44:
  				$user_variables['location'] = "CN"; // China
  				break;
  
  				case 45:
  				$user_variables['location'] = "CX"; // Christmas Island
  				break;
  
  				case 46:
  				$user_variables['location'] = "CC"; // Cocos (keeling) Islands
  				break;
  
  				case 47:
  				$user_variables['location'] = "CO"; // Colombia
  				break;
  
  				case 48:
  				$user_variables['location'] = "KM"; // Comoros
  				break;
  
  				case 49:
  				$user_variables['location'] = "CG"; // Congo
  				break;
  
  				case 50:
  				$user_variables['location'] = "CD"; // Congo, The Democratic Republic Of The
  				break;
  
  				case 51:
  				$user_variables['location'] = "CK"; // Cook Islands
  				break;
  
  				case 52:
  				$user_variables['location'] = "CR"; // Costa Rica
  				break;
  
  				case 53:
  				$user_variables['location'] = "CI"; // Cote D'ivoire
  				break;
  
  				case 54:
  				$user_variables['location'] = "HR"; // Croatia
  				break;
  
  				case 55:
  				$user_variables['location'] = "CU"; // Cuba
  				break;
  
  				case 56:
  				$user_variables['location'] = "CY"; // Cyprus
  				break;
  
  				case 57:
  				$user_variables['location'] = "CZ"; // Czech Republic
  				break;
  
  				case 58:
  				$user_variables['location'] = "DK"; // Denmark
  				break;
  
  				case 59:
  				$user_variables['location'] = "DJ"; // Djibouti
  				break;
  
  				case 60:
  				$user_variables['location'] = "DM"; // Dominica
  				break;
  
  				case 61:
  				$user_variables['location'] = "DO"; // Dominican Republic
  				break;
  
  				case 62:
  				$user_variables['location'] = "TP"; // East Timor
  				break;
  
  				case 63:
  				$user_variables['location'] = "EC"; // Ecuador
  				break;
  
  				case 64:
  				$user_variables['location'] = "EG"; // Egypt
  				break;
  
  				case 65:
  				$user_variables['location'] = "SV"; // El Salvador
  				break;
  
  				case 66:
  				$user_variables['location'] = "England"; // England
  				break;
  
  				case 67:
  				$user_variables['location'] = "EuropeanUnion"; // European Union
  				break;
  
  				case 68:
  				$user_variables['location'] = "GQ"; // Equatorial Guinea
  				break;
  
  				case 69:
  				$user_variables['location'] = "ER"; // Eritrea
  				break;
  
  				case 70:
  				$user_variables['location'] = "EE"; // Estonia
  				break;
  
  				case 71:
  				$user_variables['location'] = "ET"; // Ethiopia
  				break;
  
  				case 72:
  				$user_variables['location'] = "FK"; // Falkland Islands (malvinas)
  				break;
  
  				case 73:
  				$user_variables['location'] = "FO"; // Faroe Islands
  				break;
  
  				case 74:
  				$user_variables['location'] = "FJ"; // Fiji
  				break;
  
  				case 75:
  				$user_variables['location'] = "FI"; // Finland
  				break;
  
  				case 76:
  				$user_variables['location'] = "FR"; // France
  				break;
  
  				case 77:
  				$user_variables['location'] = "GF"; // French Guiana
  				break;
  
  				case 78:
  				$user_variables['location'] = "PF"; // French Polynesia
  				break;
  
  				case 79:
  				$user_variables['location'] = "TF"; // French Southern Territories
  				break;
  
  				case 80:
  				$user_variables['location'] = "GA"; // Gabon
  				break;
  
  				case 81:
  				$user_variables['location'] = "GM"; // Gambia
  				break;
  
  				case 82:
  				$user_variables['location'] = "GE"; // Georgia
  				break;
  
  				case 83:
  				$user_variables['location'] = "DE"; // Germany
  				break;
  
  				case 84:
  				$user_variables['location'] = "GH"; // Ghana
  				break;
  
  				case 85:
  				$user_variables['location'] = "GI"; // Gibraltar
  				break;
  
  				case 86:
  				$user_variables['location'] = "GR"; // Greece
  				break;
  
  				case 87:
  				$user_variables['location'] = "GL"; // Greenland
  				break;
  
  				case 88:
  				$user_variables['location'] = "GD"; // Grenada
  				break;
  
  				case 89:
  				$user_variables['location'] = "GP"; // Guadeloupe
  				break;
  
  				case 90:
  				$user_variables['location'] = "GU"; // Guam
  				break;
  
  				case 91:
  				$user_variables['location'] = "GT"; // Guatemala
  				break;
  
  				case 92:
  				$user_variables['location'] = "GN"; // Guinea
  				break;
  
  				case 93:
  				$user_variables['location'] = "GW"; // Guinea-bissau
  				break;
  
  				case 94:
  				$user_variables['location'] = "GY"; // Guyana
  				break;
  
  				case 95:
  				$user_variables['location'] = "HT"; // Haiti
  				break;
  
  				case 96:
  				$user_variables['location'] = "HM"; // Heard Island And Mcdonald Islands
  				break;
  
  				case 97:
  				$user_variables['location'] = "VA"; // Holy See (vatican City State)
  				break;
  
  				case 98:
  				$user_variables['location'] = "HN"; // Honduras
  				break;
  
  				case 99:
  				$user_variables['location'] = "HK"; // Hong Kong
  				break;
  
  				case 100:
  				$user_variables['location'] = "HU"; // Hungary
  				break;
  
  				case 101:
  				$user_variables['location'] = "IS"; // Iceland
  				break;
  
  				case 102:
  				$user_variables['location'] = "IN"; // India
  				break;
  
  				case 103:
  				$user_variables['location'] = "ID"; // Indonesia
  				break;
  
  				case 104:
  				$user_variables['location'] = "IR"; // Iran, Islamic Republic Of
  				break;
  
  				case 105:
  				$user_variables['location'] = "IQ"; // Iraq
  				break;
  
  				case 106:
  				$user_variables['location'] = "IE"; // Ireland
  				break;
  
  				case 107:
  				$user_variables['location'] = "IL"; // Israel
  				break;
  
  				case 108:
  				$user_variables['location'] = "IT"; // Italy
  				break;
  
  				case 109:
  				$user_variables['location'] = "JM"; // Jamaica
  				break;
  
  				case 110:
  				$user_variables['location'] = "JP"; // Japan
  				break;
  
  				case 111:
  				$user_variables['location'] = "JO"; // Jordan
  				break;
  
  				case 112:
  				$user_variables['location'] = "KZ"; // Kazakstan
  				break;
  
  				case 113:
  				$user_variables['location'] = "KE"; // Kenya
  				break;
  
  				case 114:
  				$user_variables['location'] = "KI"; // Kiribati
  				break;
  
  				case 115:
  				$user_variables['location'] = "KP"; // Korea, Democratic Peoples Republic Of
  				break;
  
  				case 116:
  				$user_variables['location'] = "KR"; // Korea, Republic Of
  				break;
  
  				case 117:
  				$user_variables['location'] = "KW"; // Kuwait
  				break;
  
  				case 118:
  				$user_variables['location'] = "KG"; // Kyrgyzstan
  				break;
  
  				case 119:
  				$user_variables['location'] = "LA"; // Lao Peoples Democratic Republic
  				break;
  
  				case 120:
  				$user_variables['location'] = "LV"; // Latvia
  				break;
  
  				case 121:
  				$user_variables['location'] = "LB"; // Lebanon
  				break;
  
  				case 122:
  				$user_variables['location'] = "LS"; // Lesotho
  				break;
  
  				case 123:
  				$user_variables['location'] = "LR"; // Liberia
  				break;
  
  				case 124:
  				$user_variables['location'] = "LY"; // Libyan Arab Jamahiriya
  				break;
  
  				case 125:
  				$user_variables['location'] = "LI"; // Liechtenstein
  				break;
  
  				case 126:
  				$user_variables['location'] = "LT"; // Lithuania
  				break;
  
  				case 127:
  				$user_variables['location'] = "LU"; // Luxembourg
  				break;
  
  				case 128:
  				$user_variables['location'] = "MO"; // Macau
  				break;
  
  				case 129:
  				$user_variables['location'] = "MK"; // Macedonia, The Former Yugoslav Republic Of
  				break;
  
  				case 130:
  				$user_variables['location'] = "MG"; // Madagascar
  				break;
  
  				case 131:
  				$user_variables['location'] = "MW"; // Malawi
  				break;
  
  				case 132:
  				$user_variables['location'] = "MY"; // Malaysia
  				break;
  
  				case 133:
  				$user_variables['location'] = "MV"; // Maldives
  				break;
  
  				case 134:
  				$user_variables['location'] = "ML"; // Mali
  				break;
  
  				case 135:
  				$user_variables['location'] = "MT"; // Malta
  				break;
  
  				case 136:
  				$user_variables['location'] = "MH"; // Marshall Islands
  				break;
  
  				case 137:
  				$user_variables['location'] = "MQ"; // Martinique
  				break;
  
  				case 138:
  				$user_variables['location'] = "MR"; // Mauritania
  				break;
  
  				case 139:
  				$user_variables['location'] = "MU"; // Mauritius
  				break;
  
  				case 140:
  				$user_variables['location'] = "YT"; // Mayotte
  				break;
  
  				case 141:
  				$user_variables['location'] = "MX"; // Mexico
  				break;
  
  				case 142:
  				$user_variables['location'] = "FM"; // Micronesia, Federated States Of
  				break;
  
  				case 143:
  				$user_variables['location'] = "MD"; // Moldova, Republic Of
  				break;
  
  				case 144:
  				$user_variables['location'] = "MC"; // Monaco
  				break;
  
  				case 145:
  				$user_variables['location'] = "MN"; // Mongolia
  				break;
  
  				case 146:
  				$user_variables['location'] = "MS"; // Montserrat
  				break;
  
  				case 147:
  				$user_variables['location'] = "MA"; // Morocco
  				break;
  
  				case 148:
  				$user_variables['location'] = "MZ"; // Mozambique
  				break;
  
  				case 149:
  				$user_variables['location'] = "MM"; // Myanmar
  				break;
  
  				case 150:
  				$user_variables['location'] = "NA"; // Namibia
  				break;
  
  				case 151:
  				$user_variables['location'] = "NR"; // Nauru
  				break;
  
  				case 152:
  				$user_variables['location'] = "NP"; // Nepal
  				break;
  
  				case 153:
  				$user_variables['location'] = "NL"; // Netherlands
  				break;
  
  				case 154:
  				$user_variables['location'] = "AN"; // Netherlands Antilles
  				break;
  
  				case 155:
  				$user_variables['location'] = "NC"; // New Caledonia
  				break;
  
  				case 156:
  				$user_variables['location'] = "NZ"; // New Zealand
  				break;
  
  				case 157:
  				$user_variables['location'] = "NI"; // Nicaragua
  				break;
  
  				case 158:
  				$user_variables['location'] = "NE"; // Niger
  				break;
  
  				case 159:
  				$user_variables['location'] = "NG"; // Nigeria
  				break;
  
  				case 160:
  				$user_variables['location'] = "NU"; // Niue
  				break;
  
  				case 161:
  				$user_variables['location'] = "NF"; // Norfolk Island
  				break;
  
  				case 162:
  				$user_variables['location'] = "MP"; // Northern Mariana Islands
  				break;
  
  				case 163:
  				$user_variables['location'] = "NO"; // Norway
  				break;
  
  				case 164:
  				$user_variables['location'] = "OM"; // Oman
  				break;
  
  				case 165:
  				$user_variables['location'] = "PK"; // Pakistan
  				break;
  
  				case 166:
  				$user_variables['location'] = "PW"; // Palau
  				break;
  
  				case 167:
  				$user_variables['location'] = "PS"; // Palestinian Territory, Occupied
  				break;
  
  				case 168:
  				$user_variables['location'] = "PA"; // Panama
  				break;
  
  				case 169:
  				$user_variables['location'] = "PG"; // Papua New Guinea
  				break;
  
  				case 170:
  				$user_variables['location'] = "PY"; // Paraguay
  				break;
  
  				case 171:
  				$user_variables['location'] = "PE"; // Peru
  				break;
  
  				case 172:
  				$user_variables['location'] = "PH"; // Philippines
  				break;
  
  				case 173:
  				$user_variables['location'] = "PN"; // Pitcairn
  				break;
  
  				case 174:
  				$user_variables['location'] = "PL"; // Poland
  				break;
  
  				case 175:
  				$user_variables['location'] = "PT"; // Portugal
  				break;
  
  				case 176:
  				$user_variables['location'] = "PR"; // Puerto Rico
  				break;
  
  				case 177:
  				$user_variables['location'] = "QA"; // Qatar
  				break;
  
  				case 178:
  				$user_variables['location'] = "RE"; // Reunion
  				break;
  
  				case 179:
  				$user_variables['location'] = "RO"; // Romania
  				break;
  
  				case 180:
  				$user_variables['location'] = "RU"; // Russian Federation
  				break;
  
  				case 181:
  				$user_variables['location'] = "RW"; // Rwanda
  				break;
  
  				case 182:
  				$user_variables['location'] = "SH"; // Saint Helena
  				break;
  
  				case 183:
  				$user_variables['location'] = "KN"; // Saint Kitts And Nevis
  				break;
  
  				case 184:
  				$user_variables['location'] = "LC"; // Saint Lucia
  				break;
  
  				case 185:
  				$user_variables['location'] = "PM"; // Saint Pierre And Miquelon
  				break;
  
  				case 186:
  				$user_variables['location'] = "VC"; // Saint Vincent And The Grenadines
  				break;
  
  				case 187:
  				$user_variables['location'] = "WS"; // Samoa
  				break;
  
  				case 188:
  				$user_variables['location'] = "SM"; // San Marino
  				break;
  
  				case 189:
  				$user_variables['location'] = "ST"; // Sao Tome And Principe
  				break;
  
  				case 190:
  				$user_variables['location'] = "SA"; // Saudi Arabia
  				break;
  
  				case 191:
  				$user_variables['location'] = "Scotland"; // Scotland
  				break;
  
  				case 192:
  				$user_variables['location'] = "SN"; // Senegal
  				break;
  
  				case 193:
  				$user_variables['location'] = "SC"; // Seychelles
  				break;
  
  				case 194:
  				$user_variables['location'] = "SL"; // Sierra Leone
  				break;
  
  				case 195:
  				$user_variables['location'] = "SG"; // Singapore
  				break;
  
  				case 196:
  				$user_variables['location'] = "SK"; // Slovakia
  				break;
  
  				case 197:
  				$user_variables['location'] = "SI"; // Slovenia
  				break;
  
  				case 198:
  				$user_variables['location'] = "SB"; // Solomon Islands
  				break;
  
  				case 199:
  				$user_variables['location'] = "SO"; // Somalia
  				break;
  
  				case 200:
  				$user_variables['location'] = "ZA"; // South Africa
  				break;
  
  				case 201:
  				$user_variables['location'] = "GS"; // South Georgia And The South Sandwich Islands
  				break;
  
  				case 202:
  				$user_variables['location'] = "ES"; // Spain
  				break;
  
  				case 203:
  				$user_variables['location'] = "LK"; // Sri Lanka
  				break;
  
  				case 204:
  				$user_variables['location'] = "SD"; // Sudan
  				break;
  
  				case 205:
  				$user_variables['location'] = "SR"; // Suriname
  				break;
  
  				case 206:
  				$user_variables['location'] = "SJ"; // Svalbard And Jan Mayen
  				break;
  
  				case 207:
  				$user_variables['location'] = "SZ"; // Swaziland
  				break;
  
  				case 208:
  				$user_variables['location'] = "SE"; // Sweden
  				break;
  
  				case 209:
  				$user_variables['location'] = "CH"; // Switzerland
  				break;
  
  				case 210:
  				$user_variables['location'] = "SY"; // Syrian Arab Republic
  				break;
  
  				case 211:
  				$user_variables['location'] = "TW"; // Taiwan, Province Of China
  				break;
  
  				case 212:
  				$user_variables['location'] = "TJ"; // Tajikistan
  				break;
  
  				case 213:
  				$user_variables['location'] = "TZ"; // Tanzania, United Republic Of
  				break;
  
  				case 214:
  				$user_variables['location'] = "TH"; // Thailand
  				break;
  
 	 			case 215:
  				$user_variables['location'] = "TG"; // Togo
  				break;
  
  				case 216:
  				$user_variables['location'] = "TK"; // Tokelau
  				break;
  
  				case 217:
  				$user_variables['location'] = "TO"; // Tonga
  				break;
  
  				case 218:
  				$user_variables['location'] = "TT"; // Trinidad And Tobago
  				break;
  
  				case 219:
  				$user_variables['location'] = "TN"; // Tunisia
  				break;
  
  				case 220:
  				$user_variables['location'] = "TR"; // Turkey
  				break;
  
  				case 221:
  				$user_variables['location'] = "TM"; // Turkmenistan
  				break;
  
  				case 222:
  				$user_variables['location'] = "TC"; // Turks And Caicos Islands
  				break;
  
  				case 223:
  				$user_variables['location'] = "TV"; // Tuvalu
  				break;
  
  				case 224:
  				$user_variables['location'] = "UG"; // Uganda
  				break;
  
  				case 225:
  				$user_variables['location'] = "UA"; // Ukraine
  				break;
  
  				case 226:
  				$user_variables['location'] = "AE"; // United Arab Emirates
  				break;
  
  				case 227:
  				$user_variables['location'] = "GB"; // United Kingdom
  				break;
  
  				case 228:
  				$user_variables['location'] = "US"; // United States
  				break;
  
  				case 229:
  				$user_variables['location'] = "UM"; // United States Minor Outlying Islands
  				break;
  
  				case 230:
  				$user_variables['location'] = "UY"; // Uruguay
  				break;
  
  				case 231:
  				$user_variables['location'] = "UZ"; // Uzbekistan
  				break;
  
  				case 232:
  				$user_variables['location'] = "VU"; // Vanuatu
  				break;
  
  				case 233:
  				$user_variables['location'] = "VE"; // Venezuela
  				break;
  
  				case 234:
  				$user_variables['location'] = "VN"; // Viet Nam
  				break;
  
  				case 235:
  				$user_variables['location'] = "VG"; // Virgin Islands, British
  				break;
  
  				case 236:
  				$user_variables['location'] = "VI"; // Virgin Islands, U.S.
  				break;
  
  				case 237:
  				$user_variables['location'] = "Wales"; // Wales
  				break;
  
  				case 238:
  				$user_variables['location'] = "WF"; // Wallis And Futuna
  				break;
  
  				case 239:
  				$user_variables['location'] = "EH"; // Western Sahara
  				break;
  
  				case 240:
  				$user_variables['location'] = "YE"; // Yemen
  				break;
  
  				case 241:
  				$user_variables['location'] = "YU"; // Yugoslavia
  				break;
  
  				case 242:
  				$user_variables['location'] = "ZM"; // Zambia
  				break;
  
  				case 243:
  				$user_variables['location'] = "ZW"; // Zimbabwe
  				break;
}
	   		$query = "SELECT * FROM ".$tbp."airports";
			$db->setQuery($query);
			$result = $db->loadRowList();
			foreach($result as $row){
				if($user_variables['hub'] == $row['0']){
					$user_variables['hub'] = $row['1'];
				}
			}
		}
		else{
			$user_variables['airline'] = ""; // custom variable for registration
			$user_variables['hub'] = ""; // custom variable for registration
			$user_variables['location'] = ""; // custom variable for registration
		}
		
		$user_variables['password'] = $userinfo->password_clear;
		$user_variables['recaptcha'] = ""; // custom variable for registration
		
		/* array to go into table phpvms_pilots */
	    $phpvms_pilots = array(
	    'pilotid' => "", // numeric character between 1 and 11 characters long
 		'firstname' => $user_variables['first_name'], // alphanumeric values between 6 and 25 characters long
 		'lastname' => $user_variables['last_name'], // alphanumeric values between 6 and 25 characters long
 		'email' => $user_variables['email_address'], // alphanumeric values between 6 and 100 characters long
 		'code' => $user_variables['airline'], // letters 3 characters long
 		'location' => $user_variables['location'], // alphanumeric values between 6 and 32 characters long
 		'hub' => $user_variables['hub'], // alphanumeric value 4 characters long
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