<?php
namespace Radical\Web\Security\Adapter;


use Radical\Web\Security\Keys\Key;

interface ISecurityAdapter
{
	/**
	 * @param $key
	 * @return Key
	 */
	function get($key);
	function add(Key $key);

	/**
	 * @param callable $call
	 * @param int $ttl
	 * @return Key
	 */
	function newKey($call, $ttl = -1);
}