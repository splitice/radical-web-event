<?php
namespace Radical\Web\Form\Security;

use Radical\Basic\Arr\Object\CollectionObject;
use Radical\Web\Session;

class KeyStorage extends CollectionObject {
	const USE_REDIS = true;//TODO: cleanup
	
	static function AddKey(Key $key){
		if(self::USE_REDIS){
			RedisStorage::set($key->getId(), $key);
		}else{
			$data = Session::$data;
			
			$data->lock_open();
			
			if($data instanceof \Radical\Web\Session\Storage\Internal)
				$data->refresh();

			$temp = null;
			if(!isset(Session::$data['form_security'])){
				$temp = new static();
			}else{
				$temp = $data['form_security'];
			}
			$temp->Add($key->getId(), $key);
			$data['form_security'] = $temp;
	
			$data->lock_close();
		}
	}
	
	/**
	 * @param string $key the id of the key to get
	 * @return HTML\Form\Security\Key
	 */
	static function getKey($key){
		if(self::USE_REDIS){
			$key = RedisStorage::get($key);
			if($key instanceof RedisKey){
				if(php_sapi_name() != 'cli'){
					$session_id = isset($_COOKIE["PHPSESSID"])?$_COOKIE["PHPSESSID"]:null;;
					if($key->session_id !== null && $key->session_id != $session_id){
						throw new \Exception("Security Exception, session id does not match");
					}
				}
				
				return $key;
			}else{
				return;
			}
		}else{
			if(!isset(Session::$data['form_security'])){
				throw new \Exception('No security keys in session');
			}
			return Session::$data['form_security'][$key];
		}
	}
	
	static function newKey($data){
		if(self::USE_REDIS){
			return new RedisKey($data);
		}else{
			return new Key($data);
		}
	}
}