<?php
namespace Radical\Web\Page\Handler;

use Radical\Utility\Net\URL;
use Radical\Web\Form\Security\Key;
use Radical\Web\Form\Security\KeyStorage;
use Radical\Web\Page\Controller\Special\Redirect;

trait TEventPageBase {
	protected $eventKey;
    private static $eventsProcessed = false;
	
	protected function _processEvent($post = true){
		$id = Key::fromRequest($post);
		if(!empty($id)){
			$key = KeyStorage::GetKey($id);
			if($key){
				$this->eventKey = $key;
				$result = $key->Callback();
				if($result){
					return $result;
				}
			}else{
				throw new \Exception('Event invalid (session timeout?)');
			}
		}
	}
	
	/**
	 * Intercept Execute calls and check for POST events
	 * If there is a post event submission do it.
	 *
	 * @see Web\Page\Handler.PageBase::Execute()
	 */
	function execute($method = 'GET'){
        if(!self::$eventsProcessed) {
            self::$eventsProcessed = true;

            //Check for an event
            $t = $this;
            $processed = false;
            $event_func = function () use ($t, $method, &$processed) {
                $r = $t->_processEvent($method == 'POST');
                if ($r) {
                    $processed = true;
                    $request = new PageRequest($r);
                    return $request->execute($method);
                }
            };

            if (method_exists($this, 'event_execute')) {
                $r = $this->event_execute($event_func);
            } else {
                $r = $event_func();
            }

            if ($processed)
                return $r;
        }

		//Normal execution
		return parent::Execute($method);
	}
	
	protected function event_redirect(){
		if($_SERVER['REQUEST_METHOD'] == 'POST')
			return new Redirect((string)URL::fromRequest());
		
		$url = URL::fromRequest();
		$qs = $url->getQuery();
		foreach($qs as $k=>$v){
			if(substr($k, 0, 2) == '__'){
				unset($qs[$k]);
			}
		}
		$url->setQuery($qs);

		return new Redirect((string)$url);
	}
}