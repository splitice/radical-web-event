<?php
namespace Radical\Web\Security\Adapter;


use Radical\Web\Security\Internal\BestSerialization;
use Radical\Web\Security\Keys\Key;
use Radical\Web\Security\Keys\SessionKey;

class RedisAdapter implements ISecurityAdapter
{
	const COMPRESSION_LEVEL = 6;
	const PREFIX = 'radical-event:';
	const EXPIRATION_TIME = 14400;//4h

	/**
	 * @var \Predis\Client
	 */
	private $client;

	function __construct(\Predis\Client $client = null)
	{
		if($client === null){
			$client = \Splitice\ResourceFactory::getInstance()->get('redis');
		}
		$this->client = $client;
	}

	function get($key)
	{
		$s = $this->client->get(self::PREFIX.$key);
		if(empty($s))
			return null;

		$e2 = @gzinflate($s);
		if(empty($e2)){
			return null;
		}

		$key = BestSerialization::unserialize($e2);
		if(!$key) return null;

		if(php_sapi_name() != 'cli'){
			$session_id = isset($_COOKIE["PHPSESSID"])?$_COOKIE["PHPSESSID"]:null;;
			if($key->session_id !== null && $key->session_id != $session_id){
				throw new \Exception("Security Exception, session id does not match");
			}
		}

		return $key;
	}

	function add(Key $key)
	{
		$data = BestSerialization::serialize($key);
		$data = gzdeflate($data, self::COMPRESSION_LEVEL);
		$this->client->setex(self::PREFIX.$key->getId(), self::EXPIRATION_TIME, $data);
	}

	function newKey($call, $ttl = -1)
	{
		$ret = new SessionKey($call, $ttl);
		$this->add($ret);
		return $ret;
	}
}