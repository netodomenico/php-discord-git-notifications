<?php

namespace ArsDigitalia;

class Enum {
	
	private $valueName = NULL;
	
	private function __construct($valueName){
		$this->valueName = $valueName;
	}
	
	public static function __callStatic($methodName, $arguments) {
		$className = get_called_class();
		return new $className($methodName);
	}
	
	function __toString(){
		return $this->valueName;
	}
}
