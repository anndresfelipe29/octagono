<?php

/**
 * Render store cart element.
 * @property StoreCartElementOptions $options
 */
class StoreCartElement extends StoreBaseElement {
	
	public function __construct($options) {
		parent::__construct($options);
	}
	
	protected function renderCartAction(StoreNavigation $request) {
		$urlAnchor = StoreModule::$storeAnchor ? '#'.StoreModule::$storeAnchor : '';
		$this->renderView($this->viewPath.'/cart-elem.php', array(
			'elementId' => $this->options->id,
			'count' => StoreData::countCartItems(),
			'name' => $this->options->name,
			'icon' => $this->options->icon,
			'cartUrl' => (is_null($request->defaultStorePageRoute)
					? $request->detailsUrl(null, 'cart')
					: $request->getUri($request->defaultStorePageRoute.'/cart'.$urlAnchor)
				)
		));
	}
	
	/** @param StoreCartElementOptions $options */
	public static function render(StoreNavigation $request, $options) {
		$elem = new StoreCartElement($options);
		$elem->renderCartAction($request);
	}
	
}

/**
 * @property string $id cart element identifier.
 * @property string $name cart element title.
 * @property string $icon cart element icon.
 */
class StoreCartElementOptions {}
