<?php

/**
 * @property StoreElementOptions $options
 */
class StoreElement extends StoreBaseElement {
	
	private static $sortingFuncList = array('price' => 'Price', 'date' => 'Date');
	
	public function __construct($options) {
		parent::__construct($options);
	}
	
	private function renderCartAction(StoreNavigation $request) {
		$cartData = StoreData::getCartData();
		$items = $cartData->items;
		$jsItems = array();
		foreach ($items as $item) {
			$jsItems[] = (object) array(
				'id' => $item->id,
				'name' => tr_($item->name),
				'sku' => $item->sku,
				'priceStr' => $this->formatPrice($item->price),
				'price' => $item->price,
				'quantity' => StoreData::cartItemQuantity($item)
			);
		}
		$payTransactionId = StoreData::randomHash(17);
		$payInvoiceId = StoreData::randomHash(12, true);
		
		$this->renderView($this->viewPath.'/cart.php', array(
			'elementId' => $this->options->id,
			'items' => $items,
			'hasPaymentGateways' => $this->options->hasPaymentGateways,
			'hasPaymentGatewaysFile' => ($this->options->hasPaymentGateways ? $this->getTemplateLnFile($request, 'gateways') : null),
			'storeData' => array(
				'currency' => StoreData::getCurrency(),
				'priceOptions' => StoreData::getPriceOptions(),
				'items' => $jsItems,
				'transactionId' => $payTransactionId,
				'invoiceId' => $payInvoiceId,
				'checkoutDescTpl' => ('{{name}} ('.$this->__('SKU').': {{sku}}) ('.$this->__('Price').': {{price}}) ('.$this->__('Qty').': {{qty}})'),
				'checkoutUrl' => $request->getUrl('store-submit/__GATEWAY_ID__')
			),
			'hasForm' => $this->options->hasForm,
			'hasFormFile' => ($this->options->hasForm ? $this->getTemplateLnFile($request, 'form') : null),
			'currLang' => $request->getCurrLang(),
			'payTransactionId' => $payTransactionId,
			'payInvoiceId' => $payInvoiceId,
			'payCallbackUrl' => $request->getUrl('store-callback/__GATEWAY_ID__'),
			'payReturnUrl' => $request->getUrl('store-return/__GATEWAY_ID__'),
			'payCancelUrl' => $request->getUrl('store-cancel/__GATEWAY_ID__'),
			
			'cartUrl' => $request->detailsUrl(null, 'cart'),
			'backUrl' => $request->detailsUrl(null).'#'.$this->options->anchor
		));
	}
	
	private function renderListAction(StoreNavigation $request) {
		$pageQs = $request->getQueryParam('page');
		$cppQs = $request->getQueryParam('cpp');
		$sorting = $request->getQueryParam('sort');
		$listQs = $request->getQueryParam('list');
		$paging = ($this->options->itemsPerPage > 0) ? new StoreElementPaging(
				($pageQs ? intval($pageQs) - 1 : 0),
				($cppQs ? intval($cppQs) : $this->options->itemsPerPage)
			) : null;
		if (!$listQs && $request->category && isset($request->category->viewType)) {
			$listQs = $request->category->viewType;
		}
		$tableView = ($listQs == 'table');
		
		StoreData::storeFilters($request->pageId, array(
				'page' => $pageQs,
				'cpp' => $cppQs,
				'sort' => $sorting,
				'list' => $listQs,
				'filter' => $request->getQueryParam('filter')
			));
		$filters = ($this->options->itemsPerPage > 0) ? $this->collectFilters($request, $request->category) : array();
		$filterGroups = empty($filters) ? array() : array(array(), array());
		foreach ($filters as $filter) {
			$filter->sizeClass = (isset($filter->halfSize) && $filter->halfSize) ? 'col-sm-2' : 'col-sm-4';
			if (($filter->type == 'checkbox' || $filter->type == 'radiobox') && count($filter->options) > 2) {
				$filter->sizeClass = 'col-sm-12';
				$filterGroups[1][] = $filter;
			} else {
				$filterGroups[0][] = $filter;
			}
		}
		$items = $this->getFilteredItems($request->category, $paging, $filters, $sorting);
		$categories = StoreData::getCategories(true);
		$this->renderView($this->viewPath.'/list.php', array(
			'elementId' => $this->options->id,
			'request' => $request,
			'category' => $request->category,
			'items' => $items,
			'categories' => $categories,
			'noPhotoImage' => StoreData::getNoPhotoImage(),
			'paging' => $paging,
			'filterGroups' => $filterGroups,
			'showCats' => (!$request->category || !$this->options->category || $request->category->id != $this->options->category),
			'tableFields' => ($tableView ? $this->collectTableFields($request->category) : null),
			'hasTableView' => (isset(StoreModule::$initData->hasTableView) && StoreModule::$initData->hasTableView),
			'listControls' => $this->getTemplateLnFile($request, 'list-ctrls'),
			'tableView' => $tableView,
			'sorting' => $sorting,
			'sortingFuncList' => self::$sortingFuncList,
			'urlAnchor' => '#'.$this->options->anchor,
			'cartUrl' => $request->detailsUrl(null, 'cart'),
			'sortingUrl' => $request->detailsUrl(null, null, false, array('sort' => '__SORT__'), true).'#'.$this->options->anchor,
			'thumbViewUrl' => $request->detailsUrl(null, null, false, array('list' => 'thumbs'), true).'#'.$this->options->anchor,
			'tableViewUrl' => $request->detailsUrl(null, null, false, array('list' => 'table'), true).'#'.$this->options->anchor,
			'serachUrl' => $request->detailsUrl(null).'#'.$this->options->anchor,
			'currBaseUrl' => $request->detailsUrl(null)
		));
	}
	
	private function renderDetailsAction(StoreNavigation $request) {
		$item = $request->item;
		$cats = '';
		foreach ($item->categories as $catId) {
			$cat = StoreData::getCategory($catId);
			if ($cat) $cats .= ($cats ? ', ' : '').tr_($cat->name);
		}
		
		$noPhotoImage = StoreData::getNoPhotoImage();
		$imagesRaw = isset($item->altImages) ? $item->altImages : array();
		$images = array();
		foreach ($imagesRaw as $img) { if ($img && isset($img->thumb)) $images[] = $img; }
		if ($item->image && isset($item->image->thumb)) array_unshift($images, $item->image);
		
		$jsImages = array();
		foreach ($images as $img) {
			$imgSrc = $img->zoom ? $img->zoom : $img->image;
			if (!preg_match('#^https?:\/\/#i', $imgSrc)) {
				list($imgW, $imgH) = getimagesize($request->basePath.'/'.$imgSrc);
			} else {
				list($imgW, $imgH) = array(0, 0);
			}
			$jsImages[] = array('src' => $imgSrc, 'w' => $imgW, 'h' => $imgH);
		}
		
		if (empty($images) && $noPhotoImage) $images[] = $noPhotoImage;
		
		$custFields = array();
		if (isset($item->customFields) && is_array($item->customFields)) {
			$itemType = StoreData::getItemType($item->itemType);
			$fieldValIdx = array();
			foreach ($item->customFields as $fieldVal) {
				$fieldValIdx[$fieldVal->fieldId] = $fieldVal;
			}
			
			if ($itemType) foreach ($itemType->fields as $fieldData) {
				if (!$fieldData->id || $fieldData->isHidden || !isset($fieldValIdx[$fieldData->id])) continue;
				$fieldValue = $this->stringifyFieldValue($fieldValIdx[$fieldData->id], $fieldData);
				if (is_null($fieldValue)) continue;
				$custFields[] = (object) array('name' => tr_($fieldData->name), 'value' => $fieldValue);
			}
		}
		
		$imageBlockWidth = max($this->options->imageWidth, min(count($images) * 126, 488) + 48) + 40 + 1;
		
		if ($this->options->hasForm) {
			if (is_file($this->viewPath."/form_{$request->lang}.php")) {
				$hasFormFile = $this->viewPath."/form_{$request->lang}.php";
			} else if (is_file($this->viewPath."/form.php")) {
				$hasFormFile = $this->viewPath."/form.php";
			} else {
				$hasFormFile = null;
			}
		}
		
		$filterQs = StoreData::loadFilters($request->pageId);
		
		$this->renderView($this->viewPath.'/details.php', array(
			'elementId' => $this->options->id,
			'hasForm' => $this->options->hasForm,
			'hasFormFile' => ($this->options->hasForm ? $this->getTemplateLnFile($request, 'form') : null),
			'hasCart' => $this->options->hasCart,
			'showDates' => StoreData::needToShowDates(),
			'showItemId' => StoreData::needToShowItemId(),
			'item' => $item,
			'cats' => $cats,
			'images' => $images,
			'imageBlockWidth' => $imageBlockWidth,
			'custFields' => $custFields,
			'jsImages' => $jsImages,
			'formObject' => json_encode(array(
				'name' => '(ID: {{id}}) {{name}}'.
					'{{#sku}} ('.$this->__('SKU').': {{sku}}){{/sku}}'.
					'{{#price}} ('.$this->__('Price').': {{priceStr}}){{/price}}',
				'price' => $this->formatPrice($item->price),
				'items' => array((object) array(
					'id' => $item->id,
					'name' => tr_($item->name),
					'sku' => $item->sku,
					'price' => $item->price,
					'priceStr' => $this->formatPrice($item->price),
					'qty' => 1
				))
			)),
			'cartUrl' => $request->detailsUrl(null, 'cart'),
			'backUrl' => $request->detailsUrl(null, $request->lastSelectedCategory, false, $filterQs).'#'.$this->options->anchor
		));
	}
	
	private function getTemplateLnFile($request, $name) {
		if (is_file($this->viewPath."/{$name}_{$request->lang}.php")) {
			return $this->viewPath."/{$name}_{$request->lang}.php";
		} else if (is_file($this->viewPath."/{$name}.php")) {
			return $this->viewPath."/{$name}.php";
		}
		return null;
	}
	
	protected function tableFieldValues($tableFields, $item) {
		if (isset($item->customFields) && is_array($item->customFields)) {
			$itemType = StoreData::getItemType($item->itemType);
			$fieldValIdx = array();
			foreach ($item->customFields as $fieldVal) {
				$fieldValIdx[$fieldVal->fieldId] = $fieldVal;
			}
			if ($itemType) foreach ($itemType->fields as $fieldData) {
				if (!isset($tableFields[$fieldData->type]) || !isset($fieldValIdx[$fieldData->id])) continue;
				$tableFields[$fieldData->type]->value = $this->stringifyFieldValue($fieldValIdx[$fieldData->id], $fieldData);
			}
		}
		return $tableFields;
	}
	
	/**
	 * Stringify custom field value.
	 * @param \Profis\SitePro\controller\StoreDataItemCustomFieldValue $customFieldValue custom field value descriptor.
	 * @param \Profis\SitePro\controller\StoreDataItemTypeField $fieldData custom field descriptor.
	 * @return string|null
	 */
	private function stringifyFieldValue($customFieldValue, $fieldData) {
		if (!$customFieldValue || !$fieldData) return null;
		$fieldValue = $customFieldValue->value;
		$fieldTypeData = StoreData::getItemFieldType($fieldData->type);
		if ($fieldTypeData && is_array($fieldTypeData->options) && !empty($fieldTypeData->options)) {
			$fieldValueArr = is_numeric($fieldValue) ? array($fieldValue) : $fieldValue;
			if (is_array($fieldValueArr)) {
				$fieldValueArrNew = array();
				foreach ($fieldTypeData->options as $opt) {
					foreach ($fieldValueArr as $val) {
						if ($opt->id != $val) continue;
						$fieldValueArrNew[] = tr_($opt->name);
						break;
					}
				}
				$fieldValue = implode(', ', $fieldValueArrNew);
			}
		} else {
			$rawArray = is_array($fieldValue);
			$fieldValue = tr_($fieldValue);
			// case of changing field type and not saving value
			if ($rawArray && is_object($fieldValue)) $fieldValue = tr_($fieldValue);
		}
		if (is_object($fieldValue) || is_array($fieldValue)) $fieldValue = print_r($fieldValue, true);
		if (!is_numeric($fieldValue) && !$fieldValue) return null;
		return $fieldValue;
	}
	
	private function collectTableFields($category = null) {
		$fields = array();
		$types = array();
		$items = StoreData::getItems();
		for ($i = 0, $c = count($items); $i < $c; $i++) {
			$item = $items[$i];
			if ($category && !in_array($category->id, $item->categories)) continue;
			$types[$item->itemType] = true;
		}
		$itemTypes = StoreData::getItemTypes();
		foreach ($itemTypes as $itemType) {
			if (!isset($types[$itemType->id])) continue;
			foreach ($itemType->fields as $itemTypeField) {
				$fieldTypeData = StoreData::getItemFieldType($itemTypeField->type);
				if ((!$itemTypeField->isSearchable
						&& (!isset($itemTypeField->isSearchInterval) || !$itemTypeField->isSearchInterval))
						|| !$fieldTypeData) continue;
				$field = (object) array(
					'id' => $fieldTypeData->id,
					'name' => tr_($itemTypeField->name),
					'value' => null
				);
				$fields[$field->id] = $field;
			}
		}
		return $fields;
	}
	
	private function collectFilters(StoreNavigation $request, $category = null) {
		$filters = array();
		$halfSize = false;
		if (StoreData::needToShowItemId()) {
			$halfSize = true;
			$filters[] = (object) array(
				'id' => 'id',
				'halfSize' => $halfSize,
				'name' => $this->__('ID'),
				'value' => null,
				'interval' => false,
				'type' => null,
				'options' => null
			);
		}
		$filters[] = (object) array(
			'id' => 'name',
			'halfSize' => $halfSize,
			'name' => $this->__('Text search'),
			'value' => null,
			'interval' => false,
			'type' => null,
			'options' => null
		);
		if (isset(StoreModule::$initData->hasPrices) && StoreModule::$initData->hasPrices) {
			$filters[] = (object) array(
				'id' => 'price',
				'name' => $this->__('Price'),
				'value' => null,
				'interval' => true,
				'type' => null,
				'options' => null
			);
		}
		$types = array();
		$items = StoreData::getItems();
		for ($i = 0, $c = count($items); $i < $c; $i++) {
			$item = $items[$i];
			if ($category && !in_array($category->id, $item->categories)) continue;
			$types[$item->itemType] = true;
		}
		$usedFieldTypes = array();
		$itemTypes = StoreData::getItemTypes();
		foreach ($itemTypes as $itemType) {
			if (!isset($types[$itemType->id])) continue;
			foreach ($itemType->fields as $itemTypeField) {
				if (isset($usedFieldTypes[$itemTypeField->type])) continue;
				$usedFieldTypes[$itemTypeField->type] = true;
				$fieldTypeData = StoreData::getItemFieldType($itemTypeField->type);
				if ((!$itemTypeField->isSearchable
						&& (!isset($itemTypeField->isSearchInterval) || !$itemTypeField->isSearchInterval))
						|| !$fieldTypeData) continue;
				$filter = (object) array(
					'id' => $fieldTypeData->id,
					'name' => tr_($itemTypeField->name),
					'value' => null,
					'interval' => ($itemTypeField->isSearchInterval ? true : false),
					'type' => null,
					'options' => null
				);
				if ($fieldTypeData && is_array($fieldTypeData->options) && !empty($fieldTypeData->options)) {
					$filter->type = $fieldTypeData->type;
					$filter->options = $fieldTypeData->options;
				}
				
				$filters[] = $filter;
			}
		}
		$filterQs = $request->getQueryParam('filter');
		$formData = ($filterQs && is_array($filterQs)) ? $filterQs : array();
		foreach ($filters as $filter) {
			$filter->value = (isset($formData[$filter->id]) ? $formData[$filter->id] : null);
		}
		return $filters;
	}
	
	/**
	 * Store item sorter by price function.
	 * @param \Profis\SitePro\controller\StoreDataItem $a
	 * @param \Profis\SitePro\controller\StoreDataItem $b
	 */
	protected function itemSorterByPrice($a, $b) {
		if ($a->price == $b->price) return 0;
		return ($a->price < $b->price) ? -1 : 1;
	}
	
	/**
	 * Store item sorter by date function.
	 * @param \Profis\SitePro\controller\StoreDataItem $a
	 * @param \Profis\SitePro\controller\StoreDataItem $b
	 */
	protected function itemSorterByDate($a, $b) {
		$av = isset($a->dateTimeModified) ? $a->dateTimeModified : null;
		$bv = isset($b->dateTimeModified) ? $b->dateTimeModified : null;
		if ($av == $bv) return 0;
		return ($av < $bv) ? -1 : 1;
	}
	
	/**
	 * Get filtered item list.
	 * @param \Profis\SitePro\controller\StoreDataCategory $category
	 * @param StoreElementPaging $paging
	 * @return \Profis\SitePro\controller\StoreDataItem[]
	 */
	protected function getFilteredItems($category = null, $paging = null, $filters = null, $sorting = null) {
		$list = array();
		$items = StoreData::getItems();
		if (isset(self::$sortingFuncList[$sorting])) {
			usort($items, array($this, 'itemSorterBy'.ucfirst($sorting)));
		}
		for ($i = 0, $c = count($items); $i < $c; $i++) {
			$item = $items[$i];
			if (isset($item->isHidden) && $item->isHidden) continue;
			if ($category && !in_array($category->id, $item->categories)) continue;
			$fields = array('name' => tr_($item->name), 'price' => $item->price, 'description' => tr_($item->description));
			$itemType = (isset($item->itemType) ? StoreData::getItemType($item->itemType) : null);
			if ($itemType && isset($item->customFields) && is_array($item->customFields)) {
				foreach ($item->customFields as $field) {
					$fieldData = StoreData::getItemTypeField($itemType, $field->fieldId);
					if ($fieldData) $fields[$fieldData->type] = $field->value;
				}
			}
			$skip = false;
			if ($filters && !empty($filters)) {
				foreach ($filters as $filter) {
					if (!isset($filter->value) || $filter->value === '' || ($filter->interval
								&& (!isset($filter->value['from']) || $filter->value['from'] === '')
								&& (!isset($filter->value['to']) || $filter->value['to'] === '')
							)) continue;
					if ($filter->id == 'id') {
						if ($item->id != intval($filter->value)) $skip = true;
						break;
					}
					if (!isset($fields[$filter->id])) { $skip = true; break; }
					if ($filter->type == 'dropdown') {
						if ($fields[$filter->id] != $filter->value) {
							$skip = true;
							break;
						}
					} else if ($filter->type == 'checkbox' && is_array($filter->value)) {
						$common = array_intersect($fields[$filter->id], $filter->value);
						if (empty($common)) {
							$skip = true;
							break;
						}
					} else if ($filter->type == 'radiobox') {
						if (!in_array($filter->value, $fields[$filter->id])) {
							$skip = true;
							break;
						}
					} else if ($filter->interval) {
						if (isset($filter->value['from']) && $filter->value['from'] !== '' && $filter->value['from'] > $fields[$filter->id]) {
							$skip = true;
							break;
						}
						if (isset($filter->value['to']) && $filter->value['to'] !== '' && $filter->value['to'] < $fields[$filter->id]) {
							$skip = true;
							break;
						}
					} else if ($filter->id == 'name') {
						$data = $this->simplifyText(tr_($fields[$filter->id]).(isset($fields['description']) ? ('; '.$fields['description']) : ''));
						$sdata = $this->simplifyText($filter->value);
						if (function_exists('mb_strpos') && mb_strpos($data, $sdata) === false || strpos($data, $sdata) === false) {
							$skip = true;
							break;
						}
					} else {
						$data = $this->simplifyText(tr_($fields[$filter->id]));
						$sdata = $this->simplifyText($filter->value);
						if (!($data == $sdata || function_exists('mb_strpos') && mb_strpos($data, $sdata) !== false || strpos($data, $sdata) !== false)) {
							$skip = true;
							break;
						}
					}
				}
			}
			if ($skip) continue;
			$list[] = $item;
		}
		if ($paging) {
			$paging->update(count($list));
			return array_slice($list, $paging->pageIndex * $paging->itemsPerPage, $paging->itemsPerPage);
		} else {
			return $list;
		}
	}
	
	private function simplifyText($text) {
		$mb = function_exists('mb_strtolower');
		$textLen = ($mb ? mb_strlen($text) : strlen($text));
		$textLow = ($mb ? mb_strtolower($text) : strtolower($text));
//		$res = @iconv('utf-8', 'cp1252//TRANSLIT//IGNORE', $textLow);
		$res = $this->translitToLatin($textLow);
		$resLen = mb_strlen($res);
		return ($resLen < $textLen) ? $textLow : $res;
	}
	
	/**
	 * Transliterate text to latin.
	 * @param string $text text to transliterate.
	 * @return string
	 */
	private function translitToLatin($text) {
		// transliterate cyrillic chars
		$cyrillic = array('а','б','в','г','д','е', 'ё', 'ж','з','и','й','к','л','м','н','о','п','р','с','т','у','ф','х','ц', 'ч', 'ш',   'щ','ъ','ы','ь', 'э', 'ю', 'я','А','Б','В','Г','Д','Е', 'Ё', 'Ж','З','И','Й','К','Л','М','Н','О','П','Р','С','Т','У','Ф','Х','Ц', 'Ч', 'Ш',   'Щ','Ъ','Ы','Ь', 'Э', 'Ю', 'Я');
		$latinCr =  array('a','b','v','g','d','e','yo','zh','z','i','j','k','l','m','n','o','p','r','s','t','u','f','h','c','ch','sh','shch', '','y', '','eh','yu','ya','A','B','V','G','D','E','Yo','Zh','Z','I','J','K','L','M','N','O','P','R','S','T','U','F','H','C','Ch','Sh','Shch', '','Y', '','Eh','Yu','Ya');
		$textNoCr = str_replace($cyrillic, $latinCr, $text);
		// transliterate lithuanian chars
		$lithuanian = array('ą','Ą','č','Č','ę','Ę','ė','Ė','į','Į','š','Š','ų','Ų','ū','Ū','ž','Ž');
		$latinLt =    array('a','A','c','C','e','E','e','E','i','I','s','S','u','U','u','U','z','Z');
		$textNoLt = str_replace($lithuanian, $latinLt, $textNoCr);
		$accents = array(
			'à' => 'a', 'ô' => 'o', 'ď' => 'd', 'ë' => 'e', 'ơ' => 'o',
			'ß' => 'ss', 'ă' => 'a', 'ř' => 'r', 'ț' => 't', 'ň' => 'n', 'ā' => 'a', 'ķ' => 'k',
			'ŝ' => 's', 'ỳ' => 'y', 'ņ' => 'n', 'ĺ' => 'l', 'ħ' => 'h', 'ó' => 'o',
			'ú' => 'u', 'ě' => 'e', 'é' => 'e', 'ç' => 'c', 'ẁ' => 'w', 'ċ' => 'c', 'õ' => 'o',
			'ø' => 'o', 'ģ' => 'g', 'ŧ' => 't', 'ș' => 's', 'ĉ' => 'c',
			'ś' => 's', 'î' => 'i', 'ű' => 'u', 'ć' => 'c', 'ŵ' => 'w',
			'ö' => 'oe', 'è' => 'e', 'ŷ' => 'y', 'ł' => 'l',
			'ů' => 'u', 'ş' => 's', 'ğ' => 'g', 'ļ' => 'l', 'ƒ' => 'f',
			'ẃ' => 'w', 'å' => 'a', 'ì' => 'i', 'ï' => 'i', 'ť' => 't',
			'ŗ' => 'r', 'ä' => 'ae', 'í' => 'i', 'ŕ' => 'r', 'ê' => 'e', 'ü' => 'ue', 'ò' => 'o',
			'ē' => 'e', 'ñ' => 'n', 'ń' => 'n', 'ĥ' => 'h', 'ĝ' => 'g', 'đ' => 'd', 'ĵ' => 'j',
			'ÿ' => 'y', 'ũ' => 'u', 'ŭ' => 'u', 'ư' => 'u', 'ţ' => 't', 'ý' => 'y', 'ő' => 'o',
			'â' => 'a', 'ľ' => 'l', 'ẅ' => 'w', 'ż' => 'z', 'ī' => 'i', 'ã' => 'a', 'ġ' => 'g',
			'ō' => 'o', 'ĩ' => 'i', 'ù' => 'u', 'ź' => 'z', 'á' => 'a',
			'û' => 'u', 'þ' => 'th', 'ð' => 'dh', 'æ' => 'ae', 'µ' => 'u', 'ĕ' => 'e',
			
			'Ә' => 'A','ә' => 'a', 'Ғ' => 'G','ғ' => 'g', 'Қ' => 'K','қ' => 'k',
			'Ң' => 'N','ң' => 'n', 'Ө' => 'O','ө' => 'o', 'Ұ' => 'U','ұ' => 'u',
			'Ү' => 'U','ү' => 'u', 'І' => 'Y','і' => 'y', 'Һ' => 'H','һ' => 'h', 


			'À' => 'a', 'Ô' => 'o', 'Ď' => 'd', 'Ë' => 'e', 'Ơ' => 'o',
			'ß' => 'ss','Ă' => 'a', 'Ř' => 'r', 'Ț' => 't', 'Ň' => 'n', 'Ā' => 'a', 'Ķ' => 'k',
			'Ŝ' => 's', 'Ỳ' => 'y', 'Ņ' => 'n', 'Ĺ' => 'l', 'Ħ' => 'h', 'Ó' => 'o',
			'Ú' => 'u', 'Ě' => 'e', 'É' => 'e', 'Ç' => 'c', 'Ẁ' => 'w', 'Ċ' => 'c', 'Õ' => 'o',
			'Ø' => 'o', 'Ģ' => 'g', 'Ŧ' => 't', 'Ș' => 's', 'Ĉ' => 'c',
			'Ś' => 's', 'Î' => 'i', 'Ű' => 'u', 'Ć' => 'c', 'Ŵ' => 'w',
			'Ö' => 'oe', 'Ŷ' => 'y', 'Ł' => 'l',
			'Ů' => 'u', 'Ş' => 's', 'Ğ' => 'g', 'Ļ' => 'l', 'Ƒ' => 'f',
			'Ẃ' => 'w', 'Å' => 'a', 'Ì' => 'i', 'Ï' => 'i', 'Ť' => 't',
			'Ŗ' => 'r', 'Ä' => 'ae','Í' => 'i', 'Ŕ' => 'r', 'Ê' => 'e', 'Ü' => 'ue', 'Ò' => 'o',
			'Ē' => 'e', 'Ñ' => 'n', 'Ń' => 'n', 'Ĥ' => 'h', 'Ĝ' => 'g', 'Đ' => 'd', 'Ĵ' => 'j',
			'Ÿ' => 'y', 'Ũ' => 'u', 'Ŭ' => 'u', 'Ư' => 'u', 'Ţ' => 't', 'Ý' => 'y', 'Ő' => 'o',
			'Â' => 'a', 'Ľ' => 'l', 'Ẅ' => 'w', 'Ż' => 'z', 'Ī' => 'i', 'Ã' => 'a', 'Ġ' => 'g',
			'Ō' => 'o', 'Ĩ' => 'i', 'Ù' => 'u', 'Ź' => 'z', 'Á' => 'a',
			'Û' => 'u', 'Þ' => 'th','Ð' => 'dh', 'Æ' => 'ae',			 'Ĕ' => 'e'
		);
		$textLatinN = str_replace(array_keys($accents), array_values($accents), $textNoLt);
		// transliterate other language chars
		$textLatin = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $textLatinN);
		return $textLatin;
	}
	
	/** @param StoreElementOptions $options */
	public static function render(StoreNavigation $request, $options) {
		if ($options->category && !$request->category) {
			$cat = StoreData::getCategory($options->category);
			if ($cat) {
				$request->categoryKey = null;
				$request->category = $cat;
			}
		}
		if (!StoreModule::$storeAnchor && isset($options->anchor) && $options->anchor) {
			StoreModule::$storeAnchor = $options->anchor;
		}
		
		$elem = new StoreElement($options);
		if ($request->isCart) {
			$elem->renderCartAction($request);
		} else if ($request->item) {
			$elem->renderDetailsAction($request);
		} else {
			$elem->renderListAction($request);
		}
	}
	
}

class StoreElementPaging {
	/** @var int */
	public $itemsPerPage;
	/** @var int */
	public $pageIndex = 0;
	/** @var int */
	public $pageCount;
	/** @var int */
	public $pagesInPager = 5;
	/** @var int */
	public $startPageIndex;
	/** @var int */
	public $endPageIndex;
	
	public function __construct($pageIndex, $itemsPerPage) {
		$this->pageIndex = intval($pageIndex);
		$this->itemsPerPage = intval($itemsPerPage);
	}
	
	public function update($itemCount) {
		$this->itemsPerPage = ($this->itemsPerPage > 0) ? $this->itemsPerPage : 20;
		$this->pageCount = ceil($itemCount / $this->itemsPerPage);
		if ($this->pageCount > 0) {
			$this->pageIndex = ($this->pageIndex >= 0 && $this->pageIndex < $this->pageCount) ? $this->pageIndex : 0;
		}
		$this->pagesInPager = ($this->pagesInPager > 1) ? $this->pagesInPager : 5;
		
		if ($this->pageCount > 0) {
			$this->startPageIndex = $this->pageIndex - floor($this->pagesInPager / 2);
			if ($this->startPageIndex < 0) $this->startPageIndex = 0;
			$this->endPageIndex = $this->startPageIndex + $this->pagesInPager - 1;
			if ($this->endPageIndex >= $this->pageCount) {
				$this->endPageIndex = $this->pageCount - 1;
				$iip = max($this->pagesInPager - ($this->endPageIndex - $this->startPageIndex), 0);
				$this->startPageIndex -= min($this->startPageIndex, $iip);
			}
		} else {
			$this->startPageIndex = $this->endPageIndex = 0;
		}
	}
	
}

/**
 * @property string $anchor
 * @property bool $hasPaymentGateways if true then show payment gateways in cart page
 * @property bool $hasForm if true then show form in details page
 * @property bool $hasCart if true then show add to cart button in details
 * @property int $thumbWidth Item image thumbnail width
 * @property int $thumbHeight Item image thumbnail height
 * @property int $imageWidth Item image width
 * @property int $imageHeight Item image height
 * @property int $itemsPerPage item count to show per page
 * @property int $category default category id
 * @property array $translations associative array with translations
 */
class StoreElementOptions {}
