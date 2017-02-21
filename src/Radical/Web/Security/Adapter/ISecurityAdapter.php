<?php
namespace Radical\Web\Security\Adapter;


use Radical\Web\Security\Key;

interface ISecurityAdapter
{
	function get($key);
	function add(Key $key);
	function newKey($call, $ttl = -1);
}