<?php
namespace Radical\CLI\Cron\Jobs;

use Radical\CLI\Cron\Jobs\Interfaces;
use Radical\Web\Form\Security\RedisStorage;

class RedisGc implements Interfaces\ICronJob {
	function cmp($a, $b) {
		if ($a == $b) {
			return 0;
		}
		return ($a < $b) ? -1 : 1;
	}
	
	function execute(array $arguments){
		if(!RedisStorage::$redis){
			RedisStorage::init();
		}
		$redis = RedisStorage::$redis;
		
		$deleted = 0;
		$index_key = RedisStorage::getIndexKey();
		$count = $redis->scard($index_key);
		$keys = array();
		
		foreach($redis->sMembers($index_key) as $k){
			$k = RedisStorage::PREFIX.$k;
			$ttl = $redis->ttl($k);
			if($ttl == -1){
				$redis->expire($k, 6000);
				$ttl = 6000;
			}else if($ttl == -2){
				$redis->sRemove($index_key, $k);
				continue;
			}
			if($ttl <= 10){
				$redis->delete($k);
				$redis->sRemove($index_key, $k);
				$deleted++;
			}else{
				$keys[$k] = $ttl;
			}
		}
		
		//trim db
		$targetSize = isset($arguments[0])?(int)$arguments[0]:100000;
		if($count > $targetSize){
			$its = $count - $targetSize;
	
			uasort ($keys, array($this,'cmp'));
			
			foreach($keys as $k=>$v){
				$redis->delete($k);
				$deleted++;
				unset($keys[$k]);
				
				$its--;
				if($its <= 0)
					break;
			}
		}
		
		echo "$deleted keys deleted - ", count($keys), " remaining out of $count keys.\r\n";
	}
	function getName(){
		return 'Redis';
	}
}