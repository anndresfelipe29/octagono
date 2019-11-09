
(function($) {
	'use strict';

	/**
	 * Set price field value by taking fixed decimal format into account.
	 * Note: uses field attribute 'data-fixed-decimal'.
	 * @param {jQuery} priceField field to set value for.
	 * @param {number} priceValue value to set.
	 */
	var setPriceFieldValue = function(priceField, priceValue) {
		var fd, mlp, val = priceValue;
		if (!(fd = parseInt(priceField.data('fixedDecimal'), 10)) && fd !== 0 || isNaN(fd)) {
			fd = 2;
		}
		if (!(mlp = parseInt(priceField.data('multiplier'), 10)) || isNaN(mlp)) {
			mlp = 1;
		}
		val = (val * mlp).toFixed(fd);
		priceField.val(val);
	};
	
	/**
	 * Update counter on all store cart elements.
	 * @param {number} total number of items in cart.
	 * @param {boolean=} needAnim show animation on cart element on update.
	 */
	var updateCartCounter = function(total, needAnim) {
		for (var i = 0; i < cartElenents.length; i++) {
			cartElenents[i].updateView(total, !needAnim);
		}
	};
	
	/**
	 * Initialise form and payment buttons.
	 * @param {jQuery} parent store element.
	 * @param {object} storeData cart items.
	 * @return {jQuery} payment buttons element.
	 */
	var initPayButtons = function(parent, storeData) {
		var thisCartItems = (storeData ? storeData.items : null),
			transactionId = (storeData ? storeData.transactionId : null),
			checkoutUrl = (storeData ? storeData.checkoutUrl : null),
			checkoutDescTpl = (storeData ? storeData.checkoutDescTpl : null),
			payButtons = parent.find('.wb-store-pay-btns').eq(0),
			form = parent.find('.wb-store-form').eq(0),
			inquiryBtn = parent.find('.wb-store-form-buttons').eq(0).find('.wb-store-inquiry-btn').eq(0);
		var setPayBtnOverlayVisible = function(container, visible) {
			if (visible) {
				container.children('div').css('opacity', 0.5);
				var overlay = $('<div class="wb-store-pay-btn-overlay">').append('<div class="ico-spin icon-wb-spinner">');
				container.append(overlay);
			} else {
				container.remove('.wb-store-pay-btn-overlay');
				container.children('div').css('opacity', '');
			}
		};
		if (inquiryBtn.length > 0) {
			inquiryBtn.on('click', function() {
				$(this).parent().hide();
				form.show();
			});
			if (thisCartItems) {
				form.children('.wb_form').on('submit', function() {
					var objInput = $(this.elements['object']);
					var data = {items: []};
					for (var i = 0; i < thisCartItems.length; i++) {
						var item = thisCartItems[i];
						data.items.push({name: item.name, sku: item.sku, priceStr: item.priceStr, price: item.price, qty: item.quantity});
					}
					data.totalPrice = parent.find('.wb-store-cart-sum').text();
					objInput.val(JSON.stringify(data));
				});
			}
		} else {
			payButtons.find('form[data-gateway-id]').each(function() {
				var tmp;
				if ((tmp = $(this).attr('data-onload'))) {
					if ((tmp in window) && typeof window[tmp] === 'function') window[tmp](this);
				}
				if ((tmp = $(this).attr('data-onsubmit'))) {
					var thisForm = this;
					if ((tmp in window) && typeof window[tmp] === 'function') $(this).on('submit', function() { return window[tmp](thisForm); });
				}
				$(this).on('submit', function(e, justSubmit) {
					if (justSubmit) return true;
					var thisForm = $(this),
						thisFormCont = $(this).parents('.wb-store-pay-btn').eq(0),
						gatewayId = $(this).attr('data-gateway-id'),
						price = parent.find('.wb-store-cart-sum').text(),
						order = [],
						buyer = null,
						formData = {};
					var key = $(thisForm).attr('data-transaction-id');
					if (!key) key = transactionId;

					$(thisForm.get(0).elements).each(function() {
						if (/^StoreModuleBuyer\[(.+)\]$/.test(this.name)) {
							if (!buyer) buyer = {};
							buyer[RegExp.$1] = this.value;
						} else {
							formData[encodeURIComponent(this.name)] = this.value;
						}
					});
					for (var i = 0; i < thisCartItems.length; i++) {
						var item = thisCartItems[i];
						order.push(checkoutDescTpl
							.replace('{{name}}', item.name)
							.replace('{{sku}}', item.sku)
							.replace('{{price}}', item.priceStr)
							.replace('{{qty}}', item.quantity));
					}
					setPayBtnOverlayVisible(thisFormCont, true);
					$.post(checkoutUrl.replace('__GATEWAY_ID__', gatewayId), {
						tnx_id: key,
						buyer: buyer,
						gateway_id: gatewayId,
						order: order,
						price: price,
						form: formData
					}, function(data) {
						if (('error' in data) && data.error) {
							alert(data.error);
							setPayBtnOverlayVisible(thisFormCont, false);
						} else {
							if (('redirectUrl' in data) && data.redirectUrl) {
								location.href = data.redirectUrl;
								return false;
							}
							if (('deleteFields' in data) && data.deleteFields) {
								for (var i in data.deleteFields) {
									thisForm.find('[name="' + data.deleteFields[i] + '"]').remove();
								}
							}
							if (('createFields' in data) && data.createFields) {
								for (var i in data.createFields) {
									var input = $(data.createFields[i]);
									var name = input.attr('name');
									thisForm.find('[name="' + name + '"]').remove();
									thisForm.append(data.createFields[i]);
								}
							}
							if (!('noSubmit' in data) || !data.noSubmit) {
								thisForm.trigger('submit', [true]);
							} else {
								setPayBtnOverlayVisible(thisFormCont, false);
							}
						}
					});
					return false;
				});
			});
		}
		return payButtons;
	};
	
	var addToCart = function(cartUrl, itemId) {
		$.get(cartUrl + 'add/' + itemId, {}, function(data) {
			updateCartCounter(data.total, true);
		}).error(function() {
			console.log('Error adding to cart');
		});
	};
	
	var initImageGallery = function(details, items) {
		
		var thisElem = $('body > .pswp').eq(0), thisItems = items;
		details.find('.wb-store-image').css({cursor: 'pointer'}).on('click', function() {
			var selIndex = 0, alts = details.find('.wb-store-alt-images').eq(0).find('.wb-store-alt-img');
			for (var i = 0, c = alts.length; i < c; i++) {
				if (alts.eq(i).hasClass('active')) break;
				selIndex++;
			}
			
			var loaded = 0, item;
			var onGalleryImgReady = function() {
				loaded++;
				if (loaded < thisItems.length) return;
				(new PhotoSwipe(thisElem[0], PhotoSwipeUI_Default, thisItems, { index: selIndex })).init();
			};
			for (var i = 0, c = thisItems.length; i < c; i++) {
				item = thisItems[i];
				if (!item.w || !item.h) {
					(function(item) {
						var img = new Image();
						img.onload = function() {
							item.w = this.width;
							item.h = this.height;
							onGalleryImgReady();
						};
						img.src = item.src;
					})(item);
				} else {
					onGalleryImgReady();
				}
			}
		});
		
		details.find('.wb-store-alt-img').on('click', function() {
			if ($(this).hasClass('active')) return;
			var mainImgCont = $(this).parents('.wb-store-imgs-block').children('.wb-store-image'),
				mainImg = mainImgCont.children('img'),
				thumbImg = $(this).children('img'),
				newImg;

			$(this).parent().children('div').removeClass('active');
			$(this).addClass('active');
			newImg = thumbImg.clone().css('opacity', 0);
			mainImgCont.prepend(newImg);
			mainImg.css('opacity', 0);
			newImg.css('opacity', 1);
			setTimeout(function() { mainImg.remove(); }, 300);
		});
		
		details.find('.wb-store-alt-images > span').on('click', function() {
			var altImgsCont = $(this).parents('.wb-store-alt-images'),
				imgList = altImgsCont.find('.wb-store-alt-img'),
				offsetWidth = imgList.eq(0).outerWidth(true),
				offsetCont = imgList.eq(0).parent(),
				// currOffset = parseInt(offsetCont.css('margin-left')),
				currOffset = ((/margin-left:[^-\d]*(-*\d+)[^\d]*/i.test(offsetCont.attr('style'))) ? parseInt(RegExp.$1) : 0),
				maxImgsInRow = Math.min(parseInt(offsetCont.parent().width() / offsetWidth), imgList.length),
				offset;

			var leftOffsetLim = 0;
			var rightOffsetLim = -((imgList.length - maxImgsInRow) * offsetWidth);
			if ($(this).hasClass('arrow-left')) {
				offset = Math.min((currOffset + offsetWidth), leftOffsetLim);
			} else if ($(this).hasClass('arrow-right')) {
				offset = Math.max((currOffset - offsetWidth), rightOffsetLim);
			}
			offsetCont.css('margin-left', offset + 'px');
		});
	};
	
	var findNearestStoreAnchor = function() {
		var stores = $('.wb-store');
		if (stores.length) {
			return stores.eq(0).find('.wb_anchor').attr('name');
		}
		return null;
	};
	
	var cartElenents = [],
		
		StoreModule = {
		
		currency: null,
		priceOptions : null,
		totalPrice: null,
		
		/**
		 * Initialise store item details page.
		 * @param {string} storeElementId store element id.
		 * @param {string} itemId store item id.
		 * @param {string} cartUrl store cart URL.
		 * @param {object[]} imageItems image item descriptor list.
		 */
		initStoreDetails: function(storeElementId, itemId, cartUrl, imageItems) {
			var thisCartUrl = cartUrl,
				thisItemId = itemId,
				details = $('#' + storeElementId).eq(0);
			initImageGallery(details, imageItems);
			details.find('.wb-store-cart-add-btn').eq(0).on('click', function() {
				addToCart(thisCartUrl, thisItemId);
			});
			initPayButtons(details);
		},

		/**
		 * Initialise store cart element.
		 * @param {string} storeCartElementId store cart element id.
		 * @param {string} cartUrl store cart URL.
		 */
		initStoreCartBtn: function(storeCartElementId, cartUrl) {
			var thisCartUrl = cartUrl, cart = $('#' + storeCartElementId).eq(0);
			if (!/\#/.test(thisCartUrl)) {
				var storeAnchor = findNearestStoreAnchor();
				if (storeAnchor) thisCartUrl += ('#' + storeAnchor);
			}
			cart.on('click', function() {
				location.href = thisCartUrl;
			});
			cartElenents.push({
				elem: cart,
				updateView: function(value, noAnim) {
					if (!noAnim) {
						cart.addClass('cartanim');
						setTimeout(function() { cart.removeClass('cartanim'); }, 1000);
					}
					cart.find('.store-cart-counter').text('(' + value + ')');
				}
			});
		},
		
		/**
		 * Initialise store cart page.
		 * @param {string} storeElementId store element id.
		 * @param {string} cartUrl store cart URL.
		 * @param {object} storeData mostly cart and price formating data.
		 */
		initStoreCart: function(storeElementId, cartUrl, storeData) {
			var self = this;
			var thisCartUrl = cartUrl,
				element = $('#' + storeElementId).eq(0),
				emptyElem = element.find('.wb-store-cart-empty').eq(0),
				totalsElem = element.find('.wb-store-cart-sum').eq(0),
				payButtons = initPayButtons(element, storeData),
				payBtnPrices = payButtons.find("input[value='{{price}}']");
		
			this.currency = storeData.currency ? storeData.currency : {code: 'USD', postfix: '', prefix: '$'};
			this.priceOptions = storeData.priceOptions ? storeData.priceOptions : {decimalPoint: '.', decimalPlaces: 2};
			
			var updateTotals = function() {
				var i, total = new Big(0), itemTotal, input,
					inputs = element.find('.wb-store-cart-table-quantity input');
				for (i = 0; i < inputs.length; i++) {
					input = inputs.eq(i);
					itemTotal = (new Big(input.data('quantity'))).times(input.data('price'));
					total = total.plus(itemTotal);
					input.closest('tr').find('.wb-store-cart-table-price').text(self.getFormattedPrice(itemTotal));
				};
				totalsElem.text(self.getFormattedPrice(total));
				self.totalPrice = parseFloat((total*1).toFixed(self.priceOptions.decimalPlaces));
				
				for (i = 0; i < payBtnPrices.length; i++) {
					setPriceFieldValue(payBtnPrices.eq(i), self.totalPrice);
					var tmp;
					if ((tmp = payBtnPrices.eq(i).attr('data-onchange'))) {
						if ((tmp in window) && typeof window[tmp] === 'function') window[tmp](payBtnPrices.get(i));
					}
				}
				
				if (total*1 === 0) emptyElem.show();
			};
			element.find('.wb-store-cart-table-remove > span').on('click', function() {
				var itemId = $(this).data('item-id');
				$(this).closest('tr').remove();
				updateTotals();
				$.getJSON(thisCartUrl + 'remove/' + itemId, {}, function(data) {
					if (data.total <= 0) payButtons.remove();
					updateCartCounter(data.total);
				}).error(function() {
					console.log('Error removing from cart');
				});
			});
			element.find('.wb-store-cart-table-quantity input').on('change keyup', function() {
				var input = $(this), quantity = parseInt(input.val(), 10), id = parseInt(input.data('item-id'), 10);
				if (isNaN(quantity) || quantity < 1) quantity = 1;
				if (parseInt(input.data('quantity'), 10) !== quantity) {
					input.data('quantity', quantity);
					for (var i = 0; i < storeData.items.length; i++) {
						if (storeData.items[i].id !== id) continue;
						storeData.items[i].quantity = quantity;
						break;
					}
					updateTotals();
					$.getJSON(thisCartUrl + 'update/' + input.data('item-id') + '/' + quantity, {}, function(data) {
						updateCartCounter(data.total);
					}).error(function() {
						console.log('Error updating cart');
					});
				}
			});
			updateTotals();
		},
		
		/**
		 * Initialise store listing page.
		 * @param {string} storeElementId store element id.
		 * @param {string} cartUrl store cart URL.
		 */
		initStoreList: function(storeElementId, cartUrl) {
			var store = $('#' + storeElementId).eq(0);
			var storeList = store.find('.wb-store-list').eq(0);
			var storeItems = storeList.find('.wb-store-item');
			var storeFilter = store.find('.wb-store-cat-select');
			var itemDrag = null, cancelDrag, thisCartUrl = cartUrl;
			storeFilter.on('change', function() {
				location.href = $(this.options[this.selectedIndex]).data('store-url');
			});
			storeItems.on('mousedown', function(e) {
				var item = $(this), offset = item.offset();
				itemDrag = {
					x: (e.pageX - offset.left),
					y: (e.pageY - offset.top),
					mX: e.pageX,
					mY: e.pageY,
					id: item.data('item-id'),
					elem: item,
					helper: null
				};
				e.stopPropagation();
				e.preventDefault();
				return false;
			}).on('mouseup', cancelDrag = function(e) {
				if (itemDrag) {
					for (var i = 0; i < cartElenents.length; i++) {
						var offset = cartElenents[i].elem.offset();
						var width = cartElenents[i].elem.width();
						var height = cartElenents[i].elem.height();
						if (e.pageX >= offset.left && e.pageX <= (offset.left + width)
								&& e.pageY >= offset.top && e.pageY <= (offset.top + height)) {
							addToCart(thisCartUrl, itemDrag.id);
							break;
						}
					}
					if (itemDrag.helper) {
						itemDrag.helper.remove();
						delete itemDrag.helper;
					}
					itemDrag = null;
					e.preventDefault();
					return false;
				}
			});
			$(document.body).on('mousemove', function(e) {
				if (!itemDrag) return;
				var a = (itemDrag.mX - e.pageX), b = (itemDrag.mY - e.pageY), dist = Math.sqrt(a*a + b*b);
				if (dist < 4) return; // if moved less than 4px do nothing
				if (!itemDrag.helper) {
					itemDrag.helper = $('<div>')
						.addClass('wb-store-list wb-store-drag-helper')
						.css({
							position: 'absolute',
							zIndex: 99999,
							left: 0, top: 0,
							width: itemDrag.elem.width() + 10,
							height: itemDrag.elem.height() + 10
						})
						.append(itemDrag.elem.clone().css({margin: 0}))
						.appendTo(document.body);
				}
				itemDrag.helper.css({
					left: (e.pageX - itemDrag.x),
					top: (e.pageY - itemDrag.y)
				});
				e.preventDefault();
				return false;
			});
			$(document.body).on('mouseup', cancelDrag);
		},
		
		getTotalPrice: function() {
			return this.totalPrice;
		},
		
		getFormattedPrice: function(price) {
			return (this.currency.prefix + parseFloat(price).toFixed(parseInt(this.priceOptions.decimalPlaces)).replace(/[\.,]/, this.priceOptions.decimalPoint) + this.currency.postfix);
		}
		
	};

	window.WBStoreModule = StoreModule;
})(jQuery);
