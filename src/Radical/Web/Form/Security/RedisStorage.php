<?php
namespace Radical\Web\Form\Security;

class RedisStorage {
	const COMPRESSION_LEVEL = 6;
	const PREFIX = 'radical-event:';
	const EXPIRATION_TIME = 14400;//4h
    static $redis;

	private static function serialize($o){
		$igbinary = extension_loaded('igbinary');
		if($igbinary) {
			return @igbinary_serialize($o);
		}
		return @serialize($o);
	}

	private static function unserialize($o){
		$igbinary = extension_loaded('igbinary');
		if($igbinary) {
			return @igbinary_unserialize($o);
		}
		return @unserialize($o);
	}

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
		return self::unserialize($e2);
	}

	static function set($key, $data){
		$redis = self::redis();
		$data = self::serialize($data);
		$data = gzdeflate($data, self::COMPRESSION_LEVEL);
		$res = $redis->set(self::PREFIX.$key, $data);
        $redis->expire(self::PREFIX.$key, self::EXPIRATION_TIME);
        $redis->sAdd(self::getIndexKey(), $key);
		
		/*$s = self::$redis->get($key);
		$r = igbinary_unserialize($s);
		die(var_dump($r));*/
		if(!$res){
			throw new \Exception("Failed to set key, error: ".$redis->getLastError());
		}
	}
}