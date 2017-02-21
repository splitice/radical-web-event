<?php
namespace Radical\Web\Security\Adapter;


use Radical\Database\Model\TableReferenceInstance;
use Radical\Web\Security\Internal\BestSerialization;
use Radical\Web\Security\Keys\Key;
use Radical\Web\Security\Keys\SessionKey;

class DatabaseAdapter implements ISecurityAdapter
{
	const COMPRESSION_LEVEL = 6;
	const EXPIRATION_TIME = 14400;

	/** @var TableReferenceInstance $instance */
	private $instance;

	function __construct(TableReferenceInstance $instance)
	{
		$this->instance = $instance;
	}

	function get($key)
	{
		$d = $this->instance->fromId($key);
		if(!$d)
			return null;

		$s = $d->getData();
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

		$ins = $this->instance->getNew();
		$ins->setId($key->getId());
		$ins->setData($data);
		$ins->setExpires(\Radical\DB::toTimeStamp(time() + self::EXPIRATION_TIME));
		$ins->insert();
	}

	function newKey($call, $ttl = -1)
	{
		$ret = new SessionKey($call, $ttl);
		$this->add($ret);
		return $ret;
	}
}