<div><?php
	if ($icon):
	?><img src="<?php echo htmlspecialchars($icon); ?>"
		 alt="<?php echo htmlspecialchars($name); ?>"
		 title="<?php echo htmlspecialchars($name); ?>" /><?php
	else:
	?><span><i class="store-cart-icon"></i></span><?php
	endif;
	?><span><?php
		if ($name):
		?><span><?php echo $this->noPhp($name); ?></span><?php
		endif;
		?><span class="store-cart-counter">(<?php echo $count; ?>)</span>
	</span>
</div>
<script type="text/javascript">
	$(function() { window.WBStoreModule.initStoreCartBtn('<?php echo $elementId; ?>', '<?php echo $cartUrl; ?>'); });
</script>
