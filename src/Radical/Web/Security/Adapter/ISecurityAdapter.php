<?php
namespace Radical\Web\Security\Adapter;


use Radical\Web\Security\Keys\Key;

interface ISecurityAdapter
{
	function get($key);
	function add(Key $key);
	function newKey($call, $ttl = -1);
}