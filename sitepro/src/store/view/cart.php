<?php
/* @var $this StoreBaseElement */
/* @var $items \Profis\SitePro\controller\StoreDataItem[] */
?>
<div class="wb-store-cart-details">
	<div class="wb-store-controls">
		<div>
			<a class="wb-store-back btn btn-default"
			   href="<?php echo htmlspecialchars($backUrl); ?>">&lt;&nbsp;<?php echo $this->__('Back'); ?></a>
		</div>
	</div>
	<table class="wb-store-cart-table">
		<thead>
			<tr>
				<th style="width: 1%;">&nbsp;</th>
				<th>&nbsp;</th>
				<th style="width: 1%;"><?php echo $this->__('Qty'); ?></th>
				<th style="width: 1%;"><?php echo $this->__('Price'); ?></th>
				<th>&nbsp;</th>
			</tr>
		</thead>
		<tbody>
			<tr class="wb-store-cart-empty"<?php if (!empty($items)) echo ' style="display: none;"'; ?>>
				<td colspan="5"><?php echo $this->__('The cart is empty'); ?></td>
			</tr>
			<?php $total = 0; ?>
			<?php foreach ($items as $item): ?>
			<tr>
				<td class="wb-store-cart-table-img">
					<div style="background-image: url('<?php echo htmlspecialchars($item->image->image); ?>');" />
				</td>
				<td class="wb-store-cart-table-name"><?php
					echo $this->noPhp(tr_($item->name));
					if ($item->price && ($priceStr = $this->formatPrice($item->price))) {
						echo "&nbsp;<span>({$priceStr})</span>";
					}
				?></td>
				<td class="wb-store-cart-table-quantity">
					<input type="text" class="form-control"
						   data-item-id="<?php echo htmlspecialchars($item->id); ?>"
						   data-quantity="<?php echo StoreData::cartItemQuantity($item); ?>"
						   data-price="<?php echo floatval($item->price); ?>"
						   value="<?php echo StoreData::cartItemQuantity($item); ?>"/>
				</td>
				<td class="wb-store-cart-table-price"><?php
					if ($item->price && $this->formatPrice($item->price)) {
						$quantity = StoreData::cartItemQuantity($item);
						$total += $item->price * $quantity;
						echo $this->formatPrice($item->price * $quantity);
					} else {
						echo '&mdash;';
					}
				?></td>
				<td class="wb-store-cart-table-remove">
					<span title="<?php echo htmlspecialchars($this->__('Remove')); ?>"
						  data-item-id="<?php echo htmlspecialchars($item->id); ?>">&times;</span>
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
		<tfoot>
			<tr>
				<th colspan="3" class="wb-store-cart-table-totals">&nbsp;<?php echo $this->__('Total'); ?>:</th>
				<td class="wb-store-cart-sum"><?php echo $this->formatPrice($total); ?></td>
				<td>&nbsp;</td>
			</tr>
		</tfoot>
	</table>
<?php if (!empty($items)): ?>
	<?php if ($hasPaymentGateways): ?>
		<?php if ($hasPaymentGatewaysFile) require $hasPaymentGatewaysFile; ?>
	<?php endif; ?>
<?php endif; ?>
<?php if (!$hasPaymentGateways && $hasForm): ?>
	<div class="wb-store-pay-btns">
		<?php if ($hasFormFile): ?>
			<?php if (empty($items)): ?> <div style="display: none;"> <?php endif; ?>
				<?php require $hasFormFile; ?>
			<?php if (empty($items)): ?> </div> <?php endif; ?>
		<?php endif; ?>
	</div>
<?php endif; ?>
</div>
<script type="text/javascript">
	$(function() { window.WBStoreModule.initStoreCart('<?php echo $elementId; ?>', '<?php echo $cartUrl; ?>', <?php echo json_encode($storeData); ?>); });
</script>
