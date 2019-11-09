<?php

class StoreModuleBuyer {
	
	private $data;
	
	public static function create($data = null) {
		return new self($data);
	}
	
	public function __construct($data = null) {
		if ($data && is_array($data) && !empty($data)) {
			$this->setData($data);
		}
	}
	
	function getData($key = null) {
		if ($key) {
			return (isset($this->data[$key])) ? $this->data[$key] : null;
		}
		return $this->data;
	}

	function setData(array $data = array()) {
		$this->data = $data;
		return $this;
	}
	
	public function jsonSerialize() {
		return $this->data;
	}
	
}
