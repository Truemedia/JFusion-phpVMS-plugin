<?php
$logcheck = SessionManager::Get('loggedin');
if(self::$session_id == '' || $logcheck == false)
?>