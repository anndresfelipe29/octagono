<?php

abstract class PaymentGateway {
	
	private $postVars = null;
	private $getVars = null;
	private $lastError = null;
	
	protected $returnAfterCallback = false;
	
	public function __construct(array $post = array(), array $get = array()) {
		$this->postVars = $post;
		$this->getVars = $get;
		$this->init();
	}
	
	/**
	 * Initiates class.
	 */
	public function init() {}
	
	/**
	 * Get order transaction ID during gateway callback
	 * @return string
	 */
	public abstract function getTransactionId();
	
	/**
	 * Get client info array during gateway callback
	 * @return array|null
	 */
	public function getClientInfo() {
		return null;
	}
	
	/**
	 * Gets last error if set.
	 * @return mixed
	 */
	public function getLastError() {
		return $this->lastError;
	}
	
	/**
	 * Sets last error if needed. 
	 * @param mixed $error
	 */
	public function setLastError($error) {
		$this->lastError = $error;
	}

	/**
	 * Gets POST parameter
	 * @param string $name
	 * @param mixed $default
	 * @return string|null
	 */
	protected function getFormParam($name, $default = null) {
		if (isset($this->postVars[$name])) {
			return $this->postVars[$name];
		}
		return $default;
	}
	
	/**
	 * Gets GET parameter
	 * @param string $name
	 * @param mixed $default
	 * @return string|null
	 */
	protected function getQueryParam($name, $default = null) {
		if (isset($this->getVars[$name])) {
			return $this->getVars[$name];
		}
		return $default;
	}
	
	/**
	 * If true then return action should be made
	 * right after callback action.
	 * @return boolean
	 */
	public function doReturnAfterCallback() {
		return $this->returnAfterCallback;
	}

	/**
	 * Returns new form HTML fields if needed
	 * to be inserted to gateway form before submit.
	 * @param array $formVars
	 * @return string[]
	 */
	public function createFormFields($formVars) {}
	
	/**
	 * Returns URL which payment gateway should redirect by.
	 * @param array $formVars
	 * @return string
	 */
	public function createRedirectUrl($formVars) {}
	
	/**
	 * Triggers payment callback script.
	 */
	public function callback(StoreModuleOrder $order = null) {}
	
	/**
	 * Triggers payment verification script.
	 */
	public function verify() {}
	
	/**
	 * Triggers payment cancellation script.
	 */
	public function cancel() {}
	
	/**
	 * Triggers payment return script.
	 */
	public function completeCheckout() {}
	
}
