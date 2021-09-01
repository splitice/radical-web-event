<?php
namespace Radical\Web\Security\Internal;


class BestSerialization
{
	public static function serialize($o){
		$igbinary = extension_loaded('igbinary');
		if($igbinary) {
			return @igbinary_serialize($o);
		}
		return @serialize($o);
	}

	public static function unserialize($o){
		$igbinary = extension_loaded('igbinary');
		if($igbinary) {
			return @igbinary_unserialize($o);
		}
		return @unserialize($o);
	}
}