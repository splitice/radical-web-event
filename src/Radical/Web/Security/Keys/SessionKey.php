<?php
namespace Radical\Web\Security\Keys;

use Radical\Web\Session;

class SessionKey extends Key {
	public $session_id;
	
	function __construct($callback = null,$ttl = -1){
        //Protect the session of a logged-in user
        if(php_sapi_name() != 'cli') {
            if (Session::$auth->isLoggedIn()) {
                $this->session_id = isset($_COOKIE["PHPSESSID"]) ? $_COOKIE["PHPSESSID"] : null;
            }
        }
		parent::__construct($callback, $ttl);
	}
}