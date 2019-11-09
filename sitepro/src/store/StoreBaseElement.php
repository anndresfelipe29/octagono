<?php

/**
 * Base element for store to be used by all other store elements.
 */
class StoreBaseElement {
	/** @var stdClass */
	protected $options;
	/** @var string */
	protected $viewPath;
	/** @var \Profis\SitePro\controller\StoreDataCurrency */
	protected $currency;
	protected $priceOptions;
	
	public function __construct($options) {
		$this->options = $options;
		$this->viewPath = dirname(__FILE__).'/view';
		$this->currency = StoreData::getCurrency();
		$this->priceOptions = StoreData::getPriceOptions();
	}

	/**
	 * Format price to price string.
	 * @param float $price price to format.
	 * @return string
	 */
	protected function formatPrice($price) {
		return ($this->currency->prefix
				.number_format($price, intval($this->priceOptions->decimalPlaces), $this->priceOptions->decimalPoint, '')
				.$this->currency->postfix);
	}
	
	/**
	 * Escape PHP in content.
	 * @param string $content content to escape.
	 * @return string
	 */
	protected function noPhp($content) {
		return str_replace(array('<?', '?>'), array('&lt;?', '?&gt;'), $content);
	}
	
	/**
	 * Get translated message.
	 * @param string $msg translation keyword.
	 * @return string
	 */
	protected function __($msg) {
		return isset($this->options->translations[$msg]) ? $this->options->translations[$msg] : $msg;
	}
	
	/**
	 * Render template.
	 * @param string $templatePath path to template file.
	 * @param array $vars associative array with template variable values.
	 */
	protected function renderView($templatePath, $vars) {
		extract($vars);
		require $templatePath;
	}
	
}
