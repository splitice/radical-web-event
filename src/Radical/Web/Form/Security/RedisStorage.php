<?php
namespace Radical\Web\Form\Security;

class RedisStorage {
	static $redis;
	const PREFIX = 'radical-event:';

	static function init($host = '127.0.0.1', $port = 6379){

		if(!self::$redis){
			self::$redis = new \Redis();
			if(!self::$redis->connect($host, $port)){
				throw new \Exception("Could not connect to Redis server.");
			}
				
		}
	}
	
	static function getIndexKey(){
		return self::PREFIX.'_index';
	}

	static function get($key){
		self::init();
		$s = self::$redis->get(self::PREFIX.$key);
		if(empty($s))
			return null;
		
		$e2 = @gzinflate($s);
		if(empty($e2) || $e2 === false){
			$e2 = $s;
		}
		return igbinary_unserialize($e2);
	}

	static function set($key, $data){
		self::init();
		$data = igbinary_serialize($data);
		$data = gzdeflate($data, 9);
		$res = self::$redis->set(self::PREFIX.$key, $data, 6000);
		self::$redis->sAdd(self::getIndexKey(), $key);
		
		/*$s = self::$redis->get($key);
		$r = igbinary_unserialize($s);
		die(var_dump($r));*/
		if(!$res){
			throw new \Exception("Failed to set key, error: ".self::$redis->getLastError());
		}
	}
}