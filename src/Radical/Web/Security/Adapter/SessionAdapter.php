<?php
namespace Radical\Web\Security\Adapter;


use Radical\Web\Security\Keys\Key;
use Radical\Web\Session;

class SessionAdapter implements ISecurityAdapter
{
	function get($key)
	{
		if(!isset(Session::$data['form_security'])){
			throw new \Exception('No security keys in session');
		}
		return Session::$data['form_security'][$key];
	}

	function add(Key $key)
	{
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

	function newKey($call, $ttl = -1)
	{
		$ret = new Key($call, $ttl);
		$this->add($ret);
		return $ret;
	}
}