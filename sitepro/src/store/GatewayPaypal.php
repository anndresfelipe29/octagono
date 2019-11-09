<?php

class GatewayPaypal extends PaymentGateway {
	
	public function getTransactionId() {
		return $this->getFormParam('custom');
	}
	
	private function getClientName() {
		return $this->getFormParam('address_name');
	}
	
	private function getClientEmail() {
		return $this->getFormParam('receiver_email');
	}
	
	private function getClientAddress() {
		$address = array();
		if (($v = $this->getFormParam('address_street'))) $address[] = $v;
		if (($v = $this->getFormParam('address_city'))) $address[] = $v;
		if (($v = $this->getFormParam('address_country'))) $address[] = $v;
		return implode(', ', $address);
	}
	
	public function getClientInfo() {
		$info = array();
		if (($v = $this->getClientName())) $info['name'] = $v;
		if (($v = $this->getClientEmail())) $info['email'] = $v;
		if (($v = $this->getClientAddress())) $info['address'] = $v;
		return $info;
	}
	
}
