<?php

class StoreNavigation {
	/** @var string */
	public $basePath;
	/** @var string */
	public $baseUrl;
	/** @var string */
	public $defaultStorePageRoute;
	/** @var string */
	public $pageBaseUrl;
	/** @var int */
	public $pageId;
	/** @var string */
	public $lang;
	/** @var string */
	public $defLang;
	/** @var string */
	public $baseLang;
	/** @var string */
	public $categoryKey;
	/** @var string */
	public $itemKey;
	/** @var \Profis\SitePro\controller\StoreDataCategory */
	public $category;
	/** @var \Profis\SitePro\controller\StoreDataCategory */
	public $lastSelectedCategory;
	/** @var \Profis\SitePro\controller\StoreDataItem */
	public $item;
	/** @var bool */
	public $isCart = false;
	/**
	 * URL arguments.
	 * @var string[]
	 */
	public $args = array();
	/** @var string */
	private $protocol;
	/** @var string */
	private $host;
	
	public function __construct() {
		if (!isset($_SERVER['HTTPS'])) $_SERVER['HTTPS'] = null;
		$this->protocol = $this->isHttps() ? 'https' : 'http';
		$this->host = (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '127.0.0.1';
	}
	
	/**
	 * Determines whether request protocol is HTTPS.
	 * @return boolean
	 */
	private function isHttps() {
		return (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'
				|| isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1)
				|| isset($_SERVER['HTTP_X_HTTPS']) && ($_SERVER['HTTP_X_HTTPS'] == 'on' || $_SERVER['HTTP_X_HTTPS'] == 1)
				|| isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443
				|| isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] == 'https'
				|| isset($_SERVER['SERVER_PROTOCOL']) && preg_match('#https#i', $_SERVER['SERVER_PROTOCOL']));
	}
	
	/**
	 * Get URL argument by index.
	 * @param int $idx URL argument index.
	 */
	public function getArg($idx) {
		return isset($this->args[$idx]) ? $this->args[$idx] : null;
	}
	
	/**
	 * Get parameter from global POST array
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public function getFormParam($key, $default = null) {
		if (isset($_POST[$key])) {
			return $_POST[$key];
		}
		return $default;
	}
	
	/**
	 * Get POST parameters.
	 * @return array
	 */
	public function getFormParams() {
		return $_POST;
	}
	
	/**
	 * Get parameter from global GET array
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public function getQueryParam($key, $default = null) {
		if (isset($_GET[$key])) {
			return $_GET[$key];
		}
		return $default;
	}
	
	/**
	 * Get GET parameters.
	 * @return array
	 */
	public function getQueryParams() {
		return $_GET;
	}
	
	/**
	 * Build relative URL for this route.
	 * @param string $route route to user for URL building.
	 * @return string
	 */
	public function getUri($route = '') {
		return $this->baseUrl.(($this->lang == $this->defLang) ? '' : ($this->lang.'/')).ltrim($route, '/');
	}
	
	/**
	 * Build absolute URL for this route.
	 * @param string $route route to user for URL building.
	 * @return string
	 */
	public function getUrl($route = '') {
		return $this->protocol.'://'.$this->host.$this->getUri($route);
	}
	
	/**
	 * Gets currently used language on site.
	 * @return string
	 */
	public function getCurrLang() {
		return ($this->lang ? $this->lang : ($this->defLang ? $this->defLang : $this->baseLang));
	}
	
	/**
	 * Build store URL
	 * @param \Profis\SitePro\controller\StoreDataItem $item
	 * @param \Profis\SitePro\controller\StoreDataCategory|string $category
	 * @return string
	 */
	public function detailsUrl($item = null, $category = null, $noCurrent = false, $params = null, $qsa = false) {
		$catPart = ($category
				? (is_string($category) ? $category : ('store-cat-'.$category->id))
				: (($this->category && !$noCurrent && $this->categoryKey) ? $this->categoryKey : '')
			 );
		$url = $this->pageBaseUrl
				.(($catPart && !$item) ? "{$catPart}/" : '')
				.($item ? ($item->alias ? $item->alias : ('store-item-'.$item->id)) : '');
		$paramsArray = array();
		if ($qsa) $paramsArray = array_merge($paramsArray, $_GET);
		if (is_array($params)) $paramsArray = array_merge($paramsArray, $params);
		if (!empty($paramsArray)) $url .= '?'.http_build_query($paramsArray);
		return $url;
	}
	
	/**
	 * Redirect to specified URL.
	 * Note: automatically closes session if required.
	 * @param string $url URL to redirect to.
	 * @param int $responseCode HTTP response status code.
	 */
	public static function redirect($url, $responseCode = 302) {
		if (session_id()) session_write_close();
		header('Location: '.$url, true, $responseCode);
		exit();
	}
}
