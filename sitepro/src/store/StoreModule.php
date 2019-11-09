<?php

/**
 * Description of StoreModule
 */
class StoreModule {
	
	private static $gateway;
	/** @var StoreNavigation */
	public static $storeNav;
	/** @var stdClass */
	public static $initData;
	private static $translations;
	/** @var string */
	public static $sessionKey;
	/** @var string */
	public static $storeAnchor;
	
	/**
	 * Translate store module variable.
	 * @global string $store_module_translations
	 * @global string|null $lang
	 * @param string $key
	 * @return string
	 */
	private static function __($key) {
		if (!self::$translations) {
			global $store_module_translations;
			if ($store_module_translations && is_array($store_module_translations)) {
				self::$translations = $store_module_translations;
			}
		}
		global $lang;
		$langCode = $lang ? $lang : '-';
		$translated = $key;
		if (isset(self::$translations[$langCode][$key]) && self::$translations[$langCode][$key]) {
			$translated = self::$translations[$langCode][$key];
		}
		return $translated;
	}
	
	public static function init($data) {
		global $site_id;
		self::$initData = $data;
		self::$sessionKey = '__STORE_CART_DATA_'.$site_id.'__';
	}
	
	/**
	 * Get orders log file
	 * @return string
	 */
	public static function getLogFile() {
		return dirname(__FILE__).'/store.log';
	}
	
	/**
	 * Parse request to perform special actions
	 * @global int $home_page home page id
	 * @param array $page page definition as key value pair array
	 * @param string $lang current page language code
	 * @param string $urlArgs additional URL arguments
	 */
	public static function parseRequest($page, $lang, $urlArgs) {
		global $home_page;
		$request = self::handleStoreNav($page, $lang, $urlArgs);
		$out = null;
		$key = reset($urlArgs);
		if (!$page || $page['id'] == $home_page) {
			try {
				if ($key == 'store-log') {
					header('Access-Control-Allow-Origin: *', true); // allow cross domain requests
					$list = StoreModuleOrder::findAll(array());
					foreach ($list as $idx => $li) {
						$list[$idx] = $li->jsonSerialize();
					}
					self::respondWithJson($list);
				}
				else if ($key == 'store-submit') {
					if ($request->getFormParam('gateway_id')) {
						ob_start();
						$order = StoreModuleOrder::findByTransactionId($request->getFormParam('tnx_id'));
						if (!$order) $order = new StoreModuleOrder();
						$buyer = $request->getFormParam('buyer');
						if (!$buyer || !is_array($buyer) || empty($buyer)) $buyer = null;
						$order->setTransactionId($request->getFormParam('tnx_id'))
								->setGatewayId($request->getFormParam('gateway_id'))
								->setItems((($v = $request->getFormParam('order')) && is_array($v)) ? array_map('trim', $v) : array())
								->setPrice($request->getFormParam('price'))
								->setBuyer(($buyer ? StoreModuleBuyer::create($buyer) : null))
								->setType('buy')
								->setState(StoreModuleOrder::STATE_PENDING)
								->save();
						$response = array('createFields' => null, 'deleteFields' => array(), 'error' => null);
						$gateway = self::getGateway($request);
						if ($gateway) {
							$formFields = $request->getFormParam('form');
							if ($buyer) $formFields['StoreModuleBuyer'] = $buyer;
							$response['createFields'] = $gateway->createFormFields($formFields);
							$response['redirectUrl'] = $gateway->createRedirectUrl($formFields);
							$response['noSubmit'] = ($response['createFields'] === false);
							$response['error'] = $gateway->getLastError();
						}
						ob_end_clean();
						self::respondWithJson($response);
					}
					exit();
				}
				else if ($key == 'store-verify') {
					self::gatewayVerify($request);
					exit();
				}
				else if ($key == 'store-callback') {
					self::gatewayCallback($request);
					if (self::getGateway($request) && self::getGateway($request)->doReturnAfterCallback()) {
						self::gatewayReturn($request);
					}
					exit();
				}
				else if ($key == 'store-return') {
					self::gatewayReturn($request);
				}
				else if ($key == 'store-cancel') {
					self::gatewayCancel($request);
				}
				else if ($request) {
					$gateway = self::getGateway($request);
					if ($gateway && method_exists($gateway, $key)) {
						echo json_encode(call_user_func(array($gateway, $key)));
						exit();
					}
				}
			} catch (Exception $ex) {
				self::exitWithError($ex->getMessage());
			}
		}
		if (session_id() && isset($_SESSION['store_return'])) {
			$out = self::gatewayReturn($request, true);
		}
		if (session_id() && isset($_SESSION['store_cancel'])) {
			$out = self::gatewayCancel($request, true);
		}
		return $out;
	}

	/**
	 * Build store request object.
	 * @global string $base_dir
	 * @global string $base_url
	 * @global string $def_lang
	 * @global int $home_page
	 * @param array $thisPage page definition as key value pair array
	 * @param string $lang current page language code
	 * @param string[] $urlArgs additional URL arguments
	 * @return StoreNavigation
	 */
	private static function handleStoreNav($thisPage, $lang, $urlArgs) {
		global $base_dir, $base_url, $def_lang, $base_lang, $home_page, $page, $pages;
		$forcedHome = false;
		if (!$thisPage) {
			foreach ($pages as $li) {
				if ($li['id'] != $home_page) continue;
				$page = $thisPage = $li;
				$forcedHome = true;
				break;
			}
		}
		
		self::$storeNav = new StoreNavigation();
		self::$storeNav->args = $urlArgs;
		self::$storeNav->lang = $lang;
		self::$storeNav->defLang = $def_lang;
		self::$storeNav->baseLang = $base_lang;
		self::$storeNav->basePath = $base_dir;
		self::$storeNav->baseUrl = preg_replace('#^[^\:]+\:\/\/[^\/]+(?:\/|$)#', '/', $base_url);
		if (isset(self::$initData->defaultStorePageId)) {
			foreach ($pages as $li) {
				if ($li['id'] != self::$initData->defaultStorePageId) continue;
				self::$storeNav->defaultStorePageRoute = ($li['id'] != $home_page) ? tr_($li['alias']) : '';
				break;
			}
		}
		self::$storeNav->pageId = isset($thisPage['id']) ? $thisPage['id'] : null;
		self::$storeNav->pageBaseUrl = self::$storeNav->baseUrl
				.(($lang == $def_lang) ? '' : ($lang.'/'))
				.(($thisPage && $thisPage['id'] != $home_page) ? (tr_($thisPage['alias']).'/') : '');
		$pageCtrls = (isset($thisPage['controllers']) && is_array($thisPage['controllers'])) ? $thisPage['controllers'] : array();
		if (in_array('store', $pageCtrls)) {
			// If current page is store page use it as default store page for current page.
			self::$storeNav->defaultStorePageRoute = ($thisPage['id'] != $home_page) ? tr_($thisPage['alias']) : '';
			$categoryKey = (isset($urlArgs[0]) && $urlArgs[0]) ? $urlArgs[0] : null;
			if ($categoryKey == 'cart') {
				self::$storeNav->isCart = true;
				self::handleStoreCartActions(self::$storeNav);
				return;
			}
			$itemKey = (isset($urlArgs[1]) && $urlArgs[1]) ? $urlArgs[1] : null;
			if ($categoryKey == 'all') {
				$categoryKey = null;
			} else if ($categoryKey) {
				$categories = StoreData::getCategories();
				for ($i = 0, $c = count($categories); $i < $c; $i++) {
					if ('store-cat-'.$categories[$i]->id != $categoryKey) continue;
					self::$storeNav->category = $categories[$i];
					$forcedHome = false;
					break;
				}
				if (!self::$storeNav->category) {
					$categoryKey = null;
					$itemKey = (isset($urlArgs[0]) && $urlArgs[0]) ? $urlArgs[0] : null;
				}
			}
			if (isset($_SERVER['HTTP_REFERER']) && preg_match('#\/store\-cat\-(\d+)#', $_SERVER['HTTP_REFERER'], $m)) {
				self::$storeNav->lastSelectedCategory = StoreData::getCategory($m[1]);
			}
			$items = StoreData::getItems();
			for ($i = 0, $c = count($items); $i < $c; $i++) {
				if ('store-item-'.$items[$i]->id != $itemKey && (!$items[$i]->alias || $items[$i]->alias != $itemKey)) continue;
				self::$storeNav->item = $items[$i];
				$forcedHome = false;
				break;
			}
			self::$storeNav->categoryKey = $categoryKey;
			self::$storeNav->itemKey = $itemKey;
		}
		if ($forcedHome) $page = null;
		return self::$storeNav;
	}
	
	/** Handle cart action requests. */
	private static function handleStoreCartActions(StoreNavigation $request) {
		$cartAction = $request->getArg(1);
		$cartActionId = $request->getArg(2);
		if ($cartAction == 'add') {
			$cartData = StoreData::getCartData();
			$items = StoreData::getItems();
			foreach ($items as $idx => $item) {
				if ($item->id != $cartActionId) continue;
				$found = false;
				foreach ($cartData->items as $cItem) {
					if ($cItem->id != $cartActionId) continue;
					$cItem->quantity = StoreData::cartItemQuantity($cItem) + 1;
					$found = true;
					break;
				}
				if (!$found) $cartData->items[] = $item;
				break;
			}
			StoreData::setCartData($cartData);
			self::respondWithJson(array('total' => StoreData::countCartItems()));
		} else if ($cartAction == 'update') {
			$cartData = StoreData::getCartData();
			foreach ($cartData->items as $idx => $item) {
				if ($item->id != $cartActionId) continue;
				$quantityQs = $request->getArg(3);
				$cartData->items[$idx]->quantity = ($quantityQs ? intval($quantityQs) : null);
				break;
			}
			StoreData::setCartData($cartData);
			self::respondWithJson(array('total' => StoreData::countCartItems()));
		} else if ($cartAction == 'remove') {
			$cartData = StoreData::getCartData();
			foreach ($cartData->items as $idx => $item) {
				if ($item->id != $cartActionId) continue;
				unset($cartData->items[$idx]);
				break;
			}
			StoreData::setCartData($cartData);
			self::respondWithJson(array('total' => StoreData::countCartItems()));
		} else if ($cartAction == 'clear') {
			self::clearStoreCart();
			self::respondWithJson(array('total' => StoreData::countCartItems()));
		}
	}
	
	private static function clearStoreCart() {
		$cartData = StoreData::getCartData();
		$cartData->items = array();
		StoreData::setCartData($cartData);
	}
	
	private static function getGatewayIdFromRequest(StoreNavigation $request) {
		$gatewayId = $request->getQueryParam('gatewayId');
		if (!$gatewayId) $gatewayId = $request->getArg(1);
		return $gatewayId;
	}
	
	/**
	 * Get currently used gateway instance
	 * @param StoreNavigation $request store request descriptor object.
	 * @return PaymentGateway|null
	 */
	private static function getGateway(StoreNavigation $request) {
		if (!self::$gateway) {
			$gatewayId = self::getGatewayIdFromRequest($request);
			if ($gatewayId) {
				$cls = 'Gateway'.implode('', array_map('ucfirst', preg_split('#(?:_|\-|(\d))#', $gatewayId, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE)));
				$file = dirname(__FILE__).'/'.$cls.'.php';
				$dfile = dirname(__FILE__).'/PaymentGateway.php';
				if (!is_file($file)) {
					$file = dirname(__FILE__).'/../../'.$cls.'.php';
				}
				if (is_file($file) && is_file($dfile)) {
					require_once($dfile);
					require_once($file);
					self::$gateway = new $cls($request->getFormParams(), $request->getQueryParams());
				}
			}
		}
		return self::$gateway;
	}
	
	/**
	 * Verify function to verify payment from payment system
	 * @param StoreNavigation $request store request descriptor object.
	 */
	private static function gatewayVerify(StoreNavigation $request) {
		$gateway = self::getGateway($request);
		file_put_contents(dirname(__FILE__).'/store_orders_verify.log', print_r(array(
			'time' => date('Y-m-d H:i:s'),
			'gateway' => self::getGatewayIdFromRequest($request),
			'POST' => $request->getFormParams(),
			'GET' => $request->getQueryParams()
		), true)."\n\n", FILE_APPEND);
		if ($gateway) {
			$gateway->verify();
		}
	}
	
	private static function exitWithError($error) {
		echo $error;
		exit();
	}
	
	/**
	 * Callback function to complete payment from payment system
	 * @global int $store_contact_form_id
	 * @global array $forms
	 * @global string $user_domain
	 * @param StoreNavigation $request store request descriptor object.
	 */
	private static function gatewayCallback(StoreNavigation $request) {
		$gateway = self::getGateway($request);
		file_put_contents(dirname(__FILE__).'/store_orders.log', print_r(array(
			'time' => date('Y-m-d H:i:s'),
			'gateway' => self::getGatewayIdFromRequest($request),
			'gatewayOK' => ($gateway ? 'Yes' : 'No'),
			'gatewayTransactionId' => ($gateway ? $gateway->getTransactionId() : null),
			'POST' => $request->getFormParams(),
			'GET' => $request->getQueryParams()
		), true)."\n\n", FILE_APPEND);
		
		if ($gateway && $gateway->getTransactionId()) {
			$order = StoreModuleOrder::findByTransactionId($gateway->getTransactionId());
			if ($order) {
				$buyerData = $gateway->getClientInfo();
				if ($buyerData) $order->setBuyer(StoreModuleBuyer::create()->setData($buyerData));
				$order->setCompleteDateTime(date('Y-m-d H:i:s'))
						->setState(StoreModuleOrder::STATE_COMPLETE)
						->save();
				if ($order->getBuyer()) {
					global $store_contact_form_id, $forms;
					if ($store_contact_form_id) {
						foreach ($forms as $pageForms) {
							foreach ($pageForms as $formId => $form) {
								if ($formId == $store_contact_form_id) {
									global $user_domain;
									$subject = "Order #{$order->getTransactionId()} at $user_domain";
									self::sendMail($subject, self::prepareMailBody($order), $form, $request);
									break 2;
								}
							}
						}
					}
				}
			}
			$gateway->callback($order);
		}
	}
	
	private static function gatewayReturn(StoreNavigation $request, $process = false) {
		if ($process) {
			self::clearStoreCart();
			if (session_id()) {
				$_SESSION['store_return'] = null;
				unset($_SESSION['store_return']);
			}
			$out = "<script type=\"text/javascript\">".
				"$('<div>')".
					".addClass('alert alert-info')".
					".css({ position: 'fixed', right: '10px', top: '10px', zIndex: 10000 })".
					".text('".self::__('Payment has been submitted')."')".
					".prepend($('<button>')".
						".addClass('close')".
						".html(\"&nbsp;&times;\")".
						".on('click', function() {".
							"$(this).parent().remove();".
						"})".
					")".
					".appendTo('body');".
				"</script>";
			return $out;
		} else {
			$gateway = self::getGateway($request);
			if ($gateway) $gateway->completeCheckout();
			if (session_id()) { $_SESSION['store_return'] = true; }
			StoreNavigation::redirect($request->getUri());
		}
	}
	
	private static function gatewayCancel(StoreNavigation $request, $process = false) {
		if ($process) {
			if (session_id()) {
				$_SESSION['store_cancel'] = null;
				unset($_SESSION['store_cancel']);
			}
			$out = "<script type=\"text/javascript\">".
				"$('<div>')".
					".addClass('alert alert-danger')".
					".css({ position: 'fixed', right: '10px', top: '10px', zIndex: 10000 })".
					".text('".self::__('Payment has been canceled')."')".
					".prepend($('<button>')".
						".addClass('close')".
						".html(\"&nbsp;&times;\")".
						".on('click', function() {".
							"$(this).parent().remove();".
						"})".
					")".
					".appendTo('body');".
				"</script>";
			return $out;
		} else {
			$gateway = self::getGateway($request);
			if ($gateway) $gateway->cancel();
			if (session_id()) { $_SESSION['store_cancel'] = true; }
			StoreNavigation::redirect($request->getUri());
		}
	}
	
	/**
	 * Generate email body
	 * @param StoreModuleOrder $order
	 * @return string
	 */
	private static function prepareMailBody(StoreModuleOrder $order) {
		$style = "* { font: 12px Arial; line-height: 20px; }\nstrong { font-weight: bold; }";
		$rows = array();
		if ($order->getCompleteDateTime()) {
			$rows[] = '<tr>'.
					'<td><strong>Time:</strong>&nbsp;</td>'.
					'<td>'.$order->getCompleteDateTime().'</td>'.
				'</tr>';
		}
		if ($order->getGatewayId()) {
			$rows[] = '<tr>'.
					'<td><strong>Payment Gateway:</strong>&nbsp;</td>'.
					'<td>'.$order->getGatewayId().'</td>'.
				'</tr>';
		}
		if (!empty($rows)) {
			$rows[] = '<tr><td>&nbsp;</td></tr>';
		}
		if ($order->getBuyer() && $order->getBuyer()->getData()) {
			foreach ($order->getBuyer()->getData() as $k => $v) {
				if (!$v) continue;
				$rows[] = '<tr>'.
						'<td><strong>Buyer '.(function_exists('mb_ucfirst') ? mb_ucfirst($k) : ucfirst($k)).':</strong>&nbsp;</td>'.
						'<td>'.$v.'</td>'.
					'</tr>';
			}
			$rows[] = '<tr><td>&nbsp;</td></tr>';
		}
		if ($order->getItems()) {
			$rows[] = '<tr><td colspan="2"><strong>Purchase details:</strong></td></tr>';
			foreach ($order->getItems() as $item) {
				$rows[] = '<tr><td colspan="2">'.$item.'</td></tr>';
			}
			$rows[] = '<tr><td>&nbsp;</td></tr>';
		}
		if ($order->getPrice()) {
			$rows[] = '<tr>'.
					'<td><strong>Total:</strong></td>'.
					'<td><strong>'.$order->getPrice().'</strong></td>'.
				'</tr>';
		}
		$message = '<table cellspacing="5" cellpadding="0">'.implode("\n", $rows).'</table>';
		
		$html =
'<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv=Content-Type content="text/html; charset=utf-8">
		' . ($style?"<style><!--\n$style\n--></style>\n\t\t":"") . '</head>
	<body>' . $message . '</body>
</html>';
		
		return $html;
	}
	
	/**
	 * Send email to site owner
	 * @param string $subject
	 * @param string $body
	 * @param array $options
	 */
	private static function sendMail($subject, $body, $options, StoreNavigation $request) {
		if (!class_exists('PHPMailer')) {
			include $request->basePath.'/phpmailer/class.phpmailer.php';
		}
		$mailer = new PHPMailer();
		if (isset($options['smtpEnable']) && $options['smtpEnable']) {
			include $request->basePath.'/phpmailer/class.smtp.php';
			
			$mailer->isSMTP();
			$mailer->Host = ((isset($options['smtpHost']) && $options['smtpHost']) ? $options['smtpHost'] : 'localhost');
			$mailer->Port = ((isset($options['smtpPort']) && intval($options['smtpPort'])) ? intval($options['smtpPort']) : 25);
			$mailer->SMTPSecure = ((isset($options['smtpEncryption']) && $options['smtpEncryption']) ? $options['smtpEncryption'] : '');
			$mailer->SMTPAutoTLS = false;
			if (isset($options['smtpUsername']) && $options['smtpUsername'] && isset($options['smtpPassword']) && $options['smtpPassword']) {
				$mailer->SMTPAuth = true;
				$mailer->Username = ((isset($options['smtpUsername']) && $options['smtpUsername']) ? $options['smtpUsername'] : '');
				$mailer->Password = ((isset($options['smtpPassword']) && $options['smtpPassword']) ? $options['smtpPassword'] : '');
			}
			$mailer->SMTPOptions = array('ssl' => array(
				'verify_peer' => false,
				'verify_peer_name' => false,
				'allow_self_signed' => true
			));
		}
		$optsObject = json_decode($options['object'], true);
		$sender_name = $optsObject['sender_name'];
		$sender_email = (isset($options['emailFrom']) && $options['emailFrom']) ? $options['emailFrom'] : $optsObject['sender_email'];
		$mailer->SetFrom($sender_email, $sender_name);
		$mailer->AddAddress($options['email']);
		$mailer->CharSet = 'utf-8';
		$mailer->msgHTML($body);
		$mailer->AltBody = strip_tags(str_replace("</tr>", "</tr>\n", $body));
		$mailer->Subject = $subject ? $subject : $options['subject'];
		$mailer->Send();
	}
	
	/**
	 * Parse form object data string
	 * @param array $formDef form definition (associative array)
	 * @return stdClass
	 */
	private static function parseFormObject($formDef) {
		$obj = ((isset($formDef['object']) && $formDef['object']) ? json_decode($formDef['object']) : null);
		if (!$obj || !is_object($obj)) $obj = null;
		return $obj;
	}
	
	/**
	 * Render form object data
	 * @param array $formDef form definition (associative array)
	 * @param array $formData form data (input by user)
	 * @return string
	 */
	public static function renderFormObject($formDef, $formData) {
		$obj = self::parseFormObject($formDef);
		$objData = self::parseFormObject($formData);
		if (isset($obj->name) && $obj->name && $objData && isset($objData->items) && is_array($objData->items) && !empty($objData->items)) {
			$tpl = (isset($objData->name) && $objData->name && strpos($objData->name, '{{') !== false) ? $objData->name : $obj->name;
			$return = '<p><strong>';
			foreach ($objData->items as $item) {
				$val1 = preg_replace_callback('#\{\{\#([^\{\}\n]+)\}\}(.+)\{\{/\1\}\}#', function($m) use ($item) {
					return (isset($item->{$m[1]}) && $item->{$m[1]}) ? $m[2] : '';
				}, $tpl);
				$return .= preg_replace_callback('#\{\{([^\{\}\n]+)\}\}#', function($m) use ($item) {
					return isset($item->{$m[1]}) ? $item->{$m[1]} : $m[0];
				}, $val1).'<br />';
			}
			$return .= '</strong></p>';
			return $return;
		} else {
			return (isset($obj->name) && $obj->name) ? '<p><strong>'.htmlspecialchars($obj->name).'</strong></p>' : '';
		}
	}
	
	/**
	 * Log sent form as store order
	 * @param array $formDef form definition (associative array)
	 * @param array $formData form data (input by user)
	 * @param boolean $status mail send status
	 */
	public static function logForm($formDef, $formData, $status) {
		$buyerData = array();
		foreach ($formDef['fields'] as $idx => $field) {
			if (isset($field['type']) && $field['type'] == 'file') continue;
			$buyerData[tr_($field['name'])] = $formData[$idx];
		}
		$obj = self::parseFormObject($formDef);
		$objData = self::parseFormObject($formData);
		$order = null; $price = null;
		if ($objData) {
			if (isset($objData->items) && is_array($objData->items) && !empty($objData->items)) {
				foreach ($objData->items as $item) {
					$order[] = str_replace(
							array('{{name}}', '{{sku}}', '{{price}}', '{{qty}}'),
							array($item->name, $item->sku, $item->priceStr, $item->qty),
							$obj->name
						);
				}
			}
			$price = (isset($objData->totalPrice) && $objData->totalPrice) ? $objData->totalPrice : null;
		} else {
			$order = (isset($obj->name) && $obj->name) ? $obj->name : null;
			$price = (isset($obj->price) && $obj->price) ? $obj->price : null;
		}
		StoreModuleOrder::create()
				->setBuyer(StoreModuleBuyer::create($buyerData))
				->setItems((is_array($order) ? $order : ($order ? array($order) : array())))
				->setPrice($price)
				->setType('inquiry')
				->setState(StoreModuleOrder::STATE_COMPLETE)
				->setCompleteDateTime(date('Y-m-d H:i:s'))
				->save();
		
		if ($status) {
			self::clearStoreCart();
		}
	}
	
	/**
	 * Respond with JSON.
	 * @param mixed $data data to respond with.
	 */
	private static function respondWithJson($data) {
		if (session_id()) session_write_close();
		header('Content-Type: application/json; charset=utf-8', true);
		echo json_encode($data);
		exit();
	}
	
}
