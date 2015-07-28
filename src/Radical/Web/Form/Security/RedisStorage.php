<?php
namespace Radical\Web\Form\Security;

class RedisStorage {
	const PREFIX = 'radical-event:';
    static $redis;
    /**
     * @return \Redis
     */
    private static function redis(){
        return \Splitice\ResourceFactory::getInstance()->get('redis');
    }

	static function getIndexKey(){
		return self::PREFIX.'_index';
	}

	static function get($key){
		$s = self::redis()->get(self::PREFIX.$key);
		if(empty($s))
			return null;
		
		$e2 = @gzinflate($s);
		if(empty($e2) || $e2 === false){
			$e2 = $s;
		}
		return igbinary_unserialize($e2);
	}

	static function set($key, $data){
		$redis = self::redis();
		$data = igbinary_serialize($data);
		$data = gzdeflate($data, 9);
		$res = $redis->set(self::PREFIX.$key, $data);
        $redis->expire(self::PREFIX.$key, 36000);
        $redis->sAdd(self::getIndexKey(), $key);
		
		/*$s = self::$redis->get($key);
		$r = igbinary_unserialize($s);
		die(var_dump($r));*/
		if(!$res){
			throw new \Exception("Failed to set key, error: ".$redis->getLastError());
		}
	}
}