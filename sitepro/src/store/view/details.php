<?php
/* @var $this StoreElement */
/* @var $item \Profis\SitePro\controller\StoreDataItem */
?>
<div class="wb-store-details">
	<div class="wb-store-controls">
		<div>
			<a class="wb-store-back btn btn-default"
			   href="<?php echo htmlspecialchars($backUrl); ?>">&lt;&nbsp;<?php echo $this->__('Back'); ?></a>
		</div>
	</div>
	<div class="wb-store-imgs-block">
		<div class="wb-store-image">
			<?php if (empty($images) || !$images[0]->image): ?>
			<span class="wb-store-nothumb glyphicon glyphicon-picture"></span>
			<?php else: ?>
			<img src="<?php echo htmlspecialchars($images[0]->image); ?>"
				 data-zoom-href="<?php echo htmlspecialchars($images[0]->zoom); ?>"
				 alt="<?php echo htmlspecialchars(tr_($item->name)); ?>" />
			<?php endif; ?>
		</div>
		<?php if (count($images) > 1): ?>
		<br/>
		<div class="wb-store-alt-images">
			<?php if (count($images) > 2): ?>
			<span class="arrow-left"></span>
			<span class="arrow-right"></span>
			<?php endif; ?>
			<div>
				<div><?php
					foreach ($images as $image):
					?><div class="wb-store-alt-img">
						<img src="<?php echo htmlspecialchars($image->image); ?>"
							 data-zoom-href="<?php echo htmlspecialchars($image->zoom); ?>"
							 alt="<?php echo htmlspecialchars(tr_($item->name)); ?>" />
					</div><?php
					endforeach;
				?></div>
			</div>
		</div>
		<?php endif; ?>
		<?php if (isset($showDates) && $showDates && isset($item->dateTimeCreated) && $item->dateTimeCreated): ?>
		<div style="color: #c8c8c8; font-weight: normal; font-size: 14px;">
			<?php
				echo $this->__('Created').': '.date('Y-m-d', strtotime($item->dateTimeCreated))
					.((isset($item->dateTimeModified) && $item->dateTimeModified)
						? (' / '.$this->__('Modified').': '.date('Y-m-d', strtotime($item->dateTimeModified)))
						: ''
					);
			?>
		</div>
		<?php endif; ?>
	</div>
	<div class="wb-store-properties"
		<?php if (isset($imageBlockWidth) && $imageBlockWidth > 0) echo ' style="margin-left: '.$imageBlockWidth.'px;"'; ?>>
		<div class="wb-store-name">
			<?php echo $this->noPhp(tr_($item->name)); ?>
			<?php if (isset($showItemId) && $showItemId): ?>
			&nbsp;
			<span style="color: #c8c8c8; font-weight: normal; font-size: 14px;">(ID: <?php echo $this->noPhp($item->id); ?>)</span>
			<?php endif; ?>
		</div>
		
		<table class="wb-store-details-table">
			<tbody>
				<?php if ($cats): ?>
				<tr>
					<td style="padding-right: 20px; min-width: 200px;">
						<div class="wb-store-pcats"><div class="wb-store-label"><?php echo $this->__('Category'); ?>:</div></div>
					</td>
					<td><div class="wb-store-pcats"><?php echo $this->noPhp($cats); ?></div></td>
				</tr>
				<?php endif; ?>
				
				<?php if ($item->sku): ?>
				<tr>
					<td style="padding-right: 20px; min-width: 200px;">
						<div class="wb-store-sku"><div class="wb-store-label"><?php echo $this->__('SKU'); ?>:</div></div>
					</td>
					<td><div class="wb-store-sku"><?php echo $this->noPhp($item->sku); ?></div></td>
				</tr>
				<?php endif; ?>

				<?php if ($item->price && ($priceStr = $this->formatPrice($item->price))): ?>
				<tr>
					<td style="padding-right: 20px; min-width: 200px;">
						<div class="wb-store-price"><div class="wb-store-label"><?php echo $this->__('Price'); ?>:</div></div>
					</td>
					<td><div class="wb-store-price"><?php echo $priceStr; ?></div></td>
				</tr>
				<?php endif; ?>

				<?php foreach ($custFields as $field): ?>
				<tr>
					<td style="padding-right: 20px; min-width: 200px;">
						<div class="wb-store-field"><div class="wb-store-label"><?php echo $this->noPhp($field->name); ?>:</div></div>
					</td>
					<td><div class="wb-store-field"><?php echo $this->noPhp($field->value); ?></div></td>
				</tr>
				<?php endforeach; ?>
				
			</tbody>
		</table>
		
		<?php if (tr_($item->description)): ?>
		<div class="wb-store-desc" style="max-width: 768px;">
			<div class="wb-store-field" style="margin-bottom: 10px;"><div class="wb-store-label"><?php  echo $this->__('Description') ?></div></div>
			<?php echo $this->noPhp(tr_($item->description)); ?>
		</div>
		<?php endif; ?>
		
		<?php if ($hasCart): ?>
		<div class="wb-store-form-buttons">
			<button type="button" class="wb-store-cart-add-btn btn btn-success"><?php echo $this->__('Add to cart'); ?></button>
		</div>
		<?php elseif ($hasForm): ?>
			<?php if ($hasFormFile) require $hasFormFile; ?>
		<?php endif; ?>
	</div>
</div>
<script type="text/javascript">
	$(function() { window.WBStoreModule.initStoreDetails('<?php echo $elementId; ?>', '<?php echo $item->id; ?>', '<?php echo $cartUrl; ?>', <?php echo json_encode($jsImages); ?>); });
</script>
