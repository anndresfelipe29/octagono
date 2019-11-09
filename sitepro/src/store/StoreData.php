<?php

class StoreData {
	/** @var \Profis\SitePro\controller\StoreModuleSiteData */
	private static $data;
	/** @var \Profis\SitePro\controller\StoreDataCategory[] */
	private static $categoryIdx;
	/** @var \Profis\SitePro\controller\StoreDataItemType[] */
	private static $itemTypeIdx;
	/** @var \Profis\SitePro\controller\StoreDataItemFieldType[] */
	private static $itemFieldTypeIdx;

	private static function getDataFile() {
		return dirname(__FILE__).'/store.dat';
	}
	
	/** @return \Profis\SitePro\controller\StoreModuleData */
	private static function getData() {
		if (!self::$data) {
			$dataFile = self::getDataFile();
			if (is_file($dataFile)) {
				self::$data = json_decode(file_get_contents($dataFile));
			}
		}
		return self::$data;
	}
	
	public static function randomHash($len = 17, $onlyDigits = false) {
		$str = ''; $chars = 'ABCDEFGHJKLMNOPQRSTUVWXZ'; $nums = '1234567890';
		$use = $nums; if (!$onlyDigits) { $use .= $chars; }
		for ($i = 0; $i < $len; $i++) { $str .= $use[rand(0, strlen($use) - 1)]; }
		return $str;
	}
	
	/** @return bool */
	public static function needToShowDates() {
		return (($data = self::getData()) && isset($data->showDates) && $data->showDates);
	}
	
	/** @return bool */
	public static function needToShowItemId() {
		return (($data = self::getData()) && isset($data->showItemId) && $data->showItemId);
	}
	
	/**
	 * Get cart item quantity.
	 * @param \Profis\SitePro\controller\StoreDataItem $item
	 * @return int
	 */
	public static function cartItemQuantity($item) {
		if (!$item) return 0;
		return ((isset($item->quantity) && is_numeric($item->quantity) && intval($item->quantity) > 0) ? intval($item->quantity) : 1);
	}
	
	/**
	 * Get total cart item count.
	 * @return int
	 */
	public static function countCartItems() {
		$cartData = self::getCartData();
		$total = 0;
		foreach ($cartData->items as $item) {
			$total += self::cartItemQuantity($item);
		}
		return $total;
	}
	
	public static function getCartData() {
		if (!session_id()) session_start();
		$data = isset($_SESSION[StoreModule::$sessionKey]) ? $_SESSION[StoreModule::$sessionKey] : null;
		if (!$data || !is_object($data)) $data = (object) array();
		if (!isset($data->items) || !is_array($data->items)) {
			$data->items = array();
		}
		return $data;
	}
	
	public static function setCartData($data) {
		if (!session_id()) session_start();
		$_SESSION[StoreModule::$sessionKey] = $data;
	}
	
	public static function storeFilters($pageId, $filters) {
		if (!session_id()) session_start();
		$_SESSION[StoreModule::$sessionKey.'_filters_'.$pageId] = $filters;
	}
	
	public static function loadFilters($pageId) {
		if (!session_id()) session_start();
		$key = StoreModule::$sessionKey.'_filters_'.$pageId;
		$filters = isset($_SESSION[$key]) ? $_SESSION[$key] : null;
		if (!is_array($filters)) $filters = array();
		return $filters;
	}
	
	/** @return \Profis\SitePro\controller\StoreDataPaymentGateway[] */
	public static function getPaymentGateways() {
		if (($data = self::getData()) && isset($data->paymentGateways) && is_array($data->paymentGateways)) {
			return $data->paymentGateways;
		}
		return array();
	}
	
	/** @return \Profis\SitePro\controller\StoreImageData */
	public static function getNoPhotoImage() {
		if (($data = self::getData()) && isset($data->noPhotoImage) && $data->noPhotoImage) {
			return $data->noPhotoImage;
		}
		return null;
	}
	
	/** @return stdClass */
	public static function getPriceOptions() {
		if (($data = self::getData()) && isset($data->priceOptions) && is_object($data->priceOptions)) {
			return $data->priceOptions;
		}
		return (object) array();
	}
	
	/** @return \Profis\SitePro\controller\StoreDataCurrency */
	public static function getCurrency() {
		if (($data = self::getData()) && isset($data->currency) && is_object($data->currency)) {
			return $data->currency;
		}
		return (object) array();
	}
	
	/**
	 * Get category indent from depth in category tree.
	 * @param \Profis\SitePro\controller\StoreDataCategory $category
	 * @param int $lvl indent of parent item.
	 * @return int
	 */
	private static function getCategoryIndent($category, $lvl = 0) {
		if (!$category || !isset($category->parentId) || !$category->parentId) return $lvl;
		$parent = self::getCategory($category->parentId);
		if (!$parent) return $lvl;
		return self::getCategoryIndent($parent, $lvl + 1);
	}
	
	/**
	 * @param bool $indent return indented category list.
	 * @return \Profis\SitePro\controller\StoreDataCategory[]
	 */
	public static function getCategories($indent = false) {
		if (($data = self::getData()) && isset($data->categories) && is_array($data->categories)) {
			if ($indent) {
				for ($i = 0, $c = count($data->categories); $i < $c; $i++) {
					$data->categories[$i]->indent = self::getCategoryIndent($data->categories[$i]);
				}
			}
			return $data->categories;
		}
		return array();
	}

	/** @return \Profis\SitePro\controller\StoreDataItem[] */
	public static function getItems() {
		if (($data = self::getData()) && isset($data->items) && is_array($data->items)) {
			return $data->items;
		}
		return array();
	}
	
	/** @return \Profis\SitePro\controller\StoreDataItemType[] */
	public static function getItemTypes() {
		if (($data = self::getData()) && isset($data->itemTypes) && is_array($data->itemTypes)) {
			return $data->itemTypes;
		}
		return array();
	}
	
	/** @return \Profis\SitePro\controller\StoreDataItemFieldType[] */
	public static function getItemFieldTypes() {
		if (($data = self::getData()) && isset($data->itemFieldTypes) && is_array($data->itemFieldTypes)) {
			return $data->itemFieldTypes;
		}
		return array();
	}
	
	/** @return \Profis\SitePro\controller\StoreDataCategory */
	public static function getCategory($id) {
		if (!self::$categoryIdx) {
			self::$categoryIdx = array();
			$list = self::getCategories();
			for ($i = 0, $c = count($list); $i < $c; $i++) {
				self::$categoryIdx[$list[$i]->id] = $list[$i];
			}
		}
		return ($id && isset(self::$categoryIdx[$id])) ? self::$categoryIdx[$id] : null;
	}
	
	/** @return \Profis\SitePro\controller\StoreDataItemType */
	public static function getItemType($id) {
		if (!self::$itemTypeIdx) {
			self::$itemTypeIdx = array();
			$list = self::getItemTypes();
			for ($i = 0, $c = count($list); $i < $c; $i++) {
				self::$itemTypeIdx[$list[$i]->id] = $list[$i];
			}
		}
		return ($id && isset(self::$itemTypeIdx[$id])) ? self::$itemTypeIdx[$id] : null;
	}
	
	/**
	 * @param \Profis\SitePro\controller\StoreDataItemType $itemType
	 * @param int $id
	 * @return \Profis\SitePro\controller\StoreDataItemTypeField
	 */
	public static function getItemTypeField($itemType, $id) {
		if (!$itemType) return null;
		if (!isset($itemType->fieldsIdx)) {
			$itemType->fieldsIdx = array();
			for ($i = 0, $c = count($itemType->fields); $i < $c; $i++) {
				$itemType->fieldsIdx[$itemType->fields[$i]->id] = $itemType->fields[$i];
			}
		}
		return ($id && isset($itemType->fieldsIdx[$id])) ? $itemType->fieldsIdx[$id] : null;
	}
	
	/** @return \Profis\SitePro\controller\StoreDataItemFieldType */
	public static function getItemFieldType($id) {
		if (!self::$itemFieldTypeIdx) {
			self::$itemFieldTypeIdx = array();
			$list = self::getItemFieldTypes();
			for ($i = 0, $c = count($list); $i < $c; $i++) {
				self::$itemFieldTypeIdx[$list[$i]->id] = $list[$i];
			}
		}
		return ($id && isset(self::$itemFieldTypeIdx[$id])) ? self::$itemFieldTypeIdx[$id] : null;
	}
	
}
