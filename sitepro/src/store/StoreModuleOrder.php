<?php

class StoreModuleOrder {
	
	const STATE_PENDING = 'pending';
	const STATE_COMPLETE = 'complete';
	const STATE_FAILED = 'failed';
	
	const FILTER_TRANSACTION_ID = 'transactionId';
	const FILTER_STATE = 'state';
	
	private $id;
	private $transactionId;
	private $gatewayId;
	private $buyer;
	private $items;
	private $price;
	private $type;
	private $state;
	private $dateTime;
	private $completeDateTime;
	
	public static function create($transactionId = null) {
		return new self($transactionId);
	}
	
	public function __construct($transactionId = null) {
		$this->transactionId = $transactionId;
		$this->dateTime = date('Y-m-d H:i:s');
	}
	
	private function populate(array $f) {
		$this->id = isset($f['id']) ? $f['id'] : null;
		$this->transactionId = isset($f['transactionId']) ? $f['transactionId'] : (isset($f['tnx_id']) ? $f['tnx_id'] : null);
		$this->gatewayId = isset($f['gatewayId']) ? $f['gatewayId'] : (isset($f['gateway_id']) ? $f['gateway_id'] : null);
		$this->buyer = isset($f['buyer']) ? new StoreModuleBuyer($f['buyer']) : null;
		$this->items = isset($f['items']) ? $f['items'] : (isset($f['order']) ? $f['order'] : null);
		$this->price = isset($f['price']) ? $f['price'] : null;
		$this->type = isset($f['type']) ? $f['type'] : null;
		$this->state = isset($f['state']) ? $f['state'] : null;
		$this->dateTime = isset($f['dateTime']) ? $f['dateTime'] : (isset($f['time']) ? $f['time'] : null);
		$this->completeDateTime = isset($f['completeDateTime']) ? $f['completeDateTime'] : null;
	}
	
	function getId() {
		return $this->id;
	}
	
	function getTransactionId() {
		return $this->transactionId;
	}

	function getGatewayId() {
		return $this->gatewayId;
	}

	/** @return StoreModuleBuyer */
	function getBuyer() {
		return $this->buyer;
	}

	function getItems() {
		return $this->items;
	}

	function getPrice() {
		return $this->price;
	}

	function getType() {
		return $this->type;
	}
	
	function getState() {
		return $this->state;
	}

	function getDateTime() {
		return $this->dateTime;
	}

	function getCompleteDateTime() {
		return $this->completeDateTime;
	}

	function setTransactionId($transactionId) {
		$this->transactionId = $transactionId;
		return $this;
	}

	function setGatewayId($gatewayId) {
		$this->gatewayId = $gatewayId;
		return $this;
	}

	function setBuyer(StoreModuleBuyer $buyer = null) {
		$this->buyer = $buyer;
		return $this;
	}

	function setItems(array $items = array()) {
		$this->items = $items;
		return $this;
	}

	function setPrice($price) {
		$this->price = $price;
		return $this;
	}

	function setType($type) {
		$this->type = $type;
		return $this;
	}

	function setState($state) {
		$this->state = $state;
		return $this;
	}

	function setDateTime($dateTime) {
		$this->dateTime = $dateTime;
		return $this;
	}

	function setCompleteDateTime($completeDateTime) {
		$this->completeDateTime = $completeDateTime;
		return $this;
	}
	
	public function save() {
		$listArr = self::readLogFile();
		if ($this->id) {
			foreach ($listArr as $idx => $liArr) {
				if (isset($liArr['id']) && $liArr['id'] == $this->id) {
					$listArr[$idx] = $this->jsonSerialize(); break;
				}
			}
		} else {
			$thisArr = $this->jsonSerialize();
			$thisArr['id'] = sprintf("%08x", crc32(rand(1,999).'_'.microtime()));
			$listArr[] = $thisArr;
		}
		return (self::writeLogFile($listArr)) ? $this->id : null;
	}
	
	public static function findByTransactionId($transactionId) {
		if (!$transactionId) return null;
		$list = self::findAll(array(self::FILTER_TRANSACTION_ID => $transactionId));
		return array_shift($list);
	}

	public static function findAll(array $filter = array()) {
		$list = array();
		foreach (self::readLogFile() as $f) {
			if ($filter && is_array($filter)) {
				if (isset($filter[self::FILTER_TRANSACTION_ID]) && $filter[self::FILTER_TRANSACTION_ID] && (!isset($f['transactionId']) || $f['transactionId'] != $filter[self::FILTER_TRANSACTION_ID])) continue;
				if (isset($filter[self::FILTER_STATE]) && $filter[self::FILTER_STATE] && (!isset($f['state']) || $f['state'] != $filter[self::FILTER_STATE])) continue;
			}
			$o = new self();
			$o->populate($f);
			$list[] = $o;
		}
		return $list;
	}
	
	private static function readLogFile() {
		self::fixLogFile();
		$itemsFile = StoreModule::getLogFile();
		if (is_file($itemsFile)) {
			return json_decode(file_get_contents($itemsFile), true);
		}
		return array();
	}
	
	private static function writeLogFile($arr) {
		$itemsFile = StoreModule::getLogFile();
		return file_put_contents($itemsFile, json_encode($arr));
	}
	
	public function jsonSerialize() {
		return array(
			'id' => $this->id,
			'transactionId' => $this->transactionId,
			'gatewayId' => $this->gatewayId,
			'buyer' => ($this->buyer ? $this->buyer->jsonSerialize() : null),
			'items' => $this->items,
			'price' => $this->price,
			'type' => $this->type,
			'state' => $this->state,
			'dateTime' => $this->dateTime,
			'completeDateTime' => $this->completeDateTime
		);
	}
	
	/**
	 * Update log file format to have new structure.
	 */
	public static function fixLogFile() {
		$itemsFile = StoreModule::getLogFile();
		if (!is_file($itemsFile)) return;
		$data = json_decode(file_get_contents($itemsFile), true);
		$fixNeeded = (isset($data['complete']) || isset($data['pending'])); // old file format
		if ($fixNeeded) {
			$listArr = array();
			if (isset($data['complete'])) {
				foreach ($data['complete'] as $itemData) {
					$item = new self();
					$item->populate($itemData);
					$item->setState(self::STATE_COMPLETE);
					$item->setCompleteDateTime($item->getDateTime());
					$itemArr = $item->jsonSerialize();
					$itemArr['id'] = sprintf("%08x", crc32(rand(1,999).'_'.microtime()));
					$listArr[] = $itemArr;
				}
			}
			if (isset($data['pending'])) {
				foreach ($data['pending'] as $itemData) {
					$item = new self();
					$item->populate($itemData);
					$item->setState(self::STATE_PENDING);
					$itemArr = $item->jsonSerialize();
					$itemArr['id'] = sprintf("%08x", crc32(rand(1,999).'_'.microtime()));
					$listArr[] = $itemArr;
				}
			}
			self::writeLogFile($listArr);
		}
	}
	
}
