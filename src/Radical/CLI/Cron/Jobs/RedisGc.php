<?php
namespace Radical\CLI\Cron\Jobs;

use Radical\CLI\Cron\Jobs\Interfaces;
use Radical\Web\Form\Security\RedisStorage;
use Splitice\ResourceFactory;

class RedisGc implements Interfaces\ICronJob {
	function cmp($a, $b) {
		if ($a == $b) {
			return 0;
		}
		return ($a < $b) ? -1 : 1;
	}
	
	function execute(array $arguments){
		$redis = ResourceFactory::getInstance()->get('redis');

        $default_ttl = isset($arguments[1])?(int)$arguments[1]:6000;
		$deleted = 0;
        $index_key = RedisStorage::getIndexKey();
        $set_cleanup = array($index_key);
		$keys = array();
		
		foreach($redis->sMembers($index_key) as $k){
			$full_key = RedisStorage::PREFIX.$k;
			$ttl = $redis->ttl($full_key);
			if($ttl == -1){
				if($redis->exists($full_key)){
                    $redis->expire($full_key, $default_ttl);
                    $ttl = $default_ttl;
                }else{
                    $set_cleanup[] = $k;
                    continue;
                }
			}else if($ttl == -2){
                $set_cleanup[] = $k;
				continue;
			}
			if($ttl <= 10){
				$redis->delete($full_key);
                $set_cleanup[] = $k;
				$deleted++;
			}else{
				$keys[$k] = $ttl;
			}
		}
        echo "$deleted keys deleted due to ttl expiry.\r\n";


        if(count($set_cleanup) != 1){
            $ret = call_user_func_array(array($redis,'sRem'),$set_cleanup);
            if(!$ret){
                echo "FAILURE: Attempted to remove ",count($set_cleanup)," keys from tracking set due to non-existance / ttl-expire.\r\n";
            }else{
                echo count($set_cleanup)," keys removed from tracking set due to non-existance / ttl-expire.\r\n";
            }
        }

        $count = $redis->scard($index_key);
		//trim db
		$targetSize = isset($arguments[0])?(int)$arguments[0]:100000;
		if($count > $targetSize){
			$its = $count - $targetSize;
	
			uasort ($keys, array($this,'cmp'));
			
			foreach($keys as $k=>$v){
				$redis->del(RedisStorage::PREFIX.$k);
                $redis->sRem($index_key, $k);
				$deleted++;
				unset($keys[$k]);
				
				$its--;
				if($its <= 0)
					break;
			}

            echo "$deleted keys deleted to reduce the quantity.\r\n";
		}
		
		echo "total - $deleted keys deleted - ", count($keys), " remaining out of $count keys.\r\n";
	}
	function getName(){
		return 'Redis';
	}
}