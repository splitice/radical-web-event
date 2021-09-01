<?php
namespace Radical\Web\Page\Handler;

use Radical\Web\Security\Adapter\ISecurityAdapter;
use Radical\Web\Security\Keys\Key;
use Radical\Web\Page\Controller\Special\Redirect;

class EventPageRedirect extends PageBase {
	const EVENT_HANDLER = '__rp_eventA';
	const EVENT_METHOD = '__rp_eventB';

	private $object;
	private $method;
	private $data;
	private $securityField = null;
	private $eHandler;
	private $eMethod;
	
	function __construct($object, $method, $data = null){
		$this->object = $object;
		$this->method = $method;
		$this->data = $data;
	}
	
	function getObject(){
		return $this->object;
	}
	
	private function data($query_params = array()){
		//Build security field
		if($this->securityField === null){
			/** @var ISecurityAdapter $storage */
			$storage = \Splitice\ResourceFactory::getInstance()->get('event_storage');
			$this->securityField = $storage->newKey(array($this,'Execute'));
			$this->eHandler = $this->securityField->Store(serialize($this->object));
			$this->eMethod = base64_encode($this->securityField->Encrypt($this->method));
		}

		$g = $_GET;
		if(isset($g['error'])){
			unset($g['error']);
		}
		if(isset($g['eid'])){
			unset($g['eid']);
		}	
		
		//Event details
		$qs = array_merge($g, $query_params);
		$qs[self::EVENT_HANDLER] = $this->eHandler;
		$qs[self::EVENT_METHOD] = $this->eMethod;
		$qs[Key::FIELD_NAME] = $this->securityField->getId();
		
		$str_qs = '?'.http_build_query($qs);
		
		return $str_qs;
	}

    function GET(){
        return new Redirect($this->data());
    }

    function POST(){
        return $this->GET();
    }
	
	function Execute($method='GET'){
		return $this->object->{$this->method}($this->data,Key::getData(false));
	}
}