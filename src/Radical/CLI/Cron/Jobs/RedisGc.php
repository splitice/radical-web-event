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
		$redis = new \Redis();
		if(!$redis->connect('127.0.0.1', 6379)){
			throw new \Exception("Could not connect to Redis server.");
		}
		
		$count = $redis->dbSize();
		$keys = array();
		
		$index_key = RedisStorage::getIndexKey();
		foreach($redis->sMembers($index_key) as $k){
			$k = RedisStorage::PREFIX.$k;
			$ttl = $redis->ttl($k);
			if($ttl <= 10){
				$redis->delete($k);
				$redis->sRemove($index_key, $k);
			}else{
				$keys[$k] = $ttl;
			}
		}
		
		//trim db
		$targetSize = 25000;
		if($count > $targetSize){
			$its = $count - $targetSize;
	
			uasort ($keys, array($this,'cmp'));
			
			foreach($keys as $k=>$v){
				$redis->delete($k);
				
				$its--;
				if($its <= 0)
					break;
			}
		}
	}
	function getName(){
		return 'Redis';
	}
}