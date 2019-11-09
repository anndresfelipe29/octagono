<?php
/* @var $this StoreElement */
/* @var $request StoreNavigation */
/* @var $items \Profis\SitePro\controller\StoreDataItem[] */
/* @var $categories \Profis\SitePro\controller\StoreDataCategory[] */
/* @var $paging StoreElementPaging */
?>
<div class="wb-store-filters">
	<?php if (isset($showCats) && $showCats): ?>
	<select class="wb-store-cat-select form-control"
			<?php if (isset($filterGroups) && !empty($filterGroups)) echo ' style="margin-bottom: 15px;"'; ?>>
		<option value=""
				data-store-url="<?php echo htmlspecialchars($request->detailsUrl(null, null, true).$urlAnchor); ?>">
			<?php echo $this->__('All'); ?>
		</option>
		<?php foreach ($categories as $item): ?>
		<option value="<?php echo htmlspecialchars($item->id); ?>"
				<?php if ($category && $category->id == $item->id) echo ' selected="selected" '; ?>
				data-store-url="<?php echo htmlspecialchars($request->detailsUrl(null, $item, true).$urlAnchor); ?>">
			<?php echo str_repeat('&nbsp;', $item->indent * 3); ?>
			<?php echo $this->noPhp(tr_($item->name)); ?>
		</option>
		<?php endforeach; ?>
	</select>
	<?php endif; ?>
	<?php if (isset($filterGroups) && !empty($filterGroups)): ?>
	<form action="<?php echo htmlspecialchars($serachUrl); ?>" method="get">
		<input type="hidden" name="list" value="<?php echo htmlspecialchars(($tableView ? 'table' : 'list')); ?>" />
		<input type="hidden" name="sort" value="<?php echo htmlspecialchars($sorting); ?>" />
		<div class="row" style="margin-right: 0;">
			<div class="col-sm-10">
				<div class="row">
				<?php foreach ($filterGroups as $filters): ?>
					<?php foreach ($filters as $filter): ?>
					<div class="<?php echo $filter->sizeClass; ?>" style="margin-bottom: 15px;">
						<label style="white-space: nowrap;"><?php echo $this->noPhp($filter->name); ?>:</label>
						<?php if ($filter->type == 'dropdown'): ?>
						<select class="form-control"
								name="<?php echo htmlspecialchars('filter['.$filter->id.']'); ?>">
							<option value=""></option>
							<?php foreach ($filter->options as $option): ?>
							<option value="<?php echo htmlspecialchars($option->id); ?>"
									<?php if (isset($filter->value) && $filter->value == $option->id) echo ' selected="selected"'; ?>>
								<?php echo $this->noPhp(tr_($option->name)); ?>
							</option>
							<?php endforeach; ?>
						</select>
						<?php elseif ($filter->type == 'checkbox'): ?>
						<div style="margin-top: 5px;">
							<?php foreach ($filter->options as $option): ?>
							<label class="checkbox-inline">
								<input type="checkbox"
									   name="<?php echo htmlspecialchars('filter['.$filter->id.'][]'); ?>"
									   value="<?php echo htmlspecialchars($option->id); ?>"
										   <?php if (isset($filter->value) && in_array($option->id, is_array($filter->value) ? $filter->value : array($filter->value))) echo ' checked="checked"'; ?>/>
								<?php echo $this->noPhp(tr_($option->name)); ?>
							</label>
							<?php endforeach; ?>
						</div>
						<?php elseif ($filter->type == 'radiobox'): ?>
						<div style="margin-top: 5px;">
							<?php foreach ($filter->options as $option): ?>
							<label class="radio-inline">
								<input type="radio"
									   name="<?php echo htmlspecialchars('filter['.$filter->id.']'); ?>"
									   value="<?php echo htmlspecialchars($option->id); ?>"
										   <?php if (isset($filter->value) && $filter->value == $option->id) echo ' checked="checked"'; ?>/>
								<?php echo $this->noPhp(tr_($option->name)); ?>
							</label>
							<?php endforeach; ?>
						</div>
						<?php elseif ($filter->interval): ?>
						<div class="row">
							<div class="col-xs-6">
								<input class="form-control" type="text"
									   name="<?php echo htmlspecialchars('filter['.$filter->id.'][from]'); ?>"
									   placeholder="<?php echo $this->__('From'); ?>"
									   value="<?php echo htmlspecialchars(isset($filter->value['from']) ? $filter->value['from'] : ''); ?>" />
							</div>
							<div class="col-xs-6">
								<input class="form-control" type="text"
									   name="<?php echo htmlspecialchars('filter['.$filter->id.'][to]'); ?>"
									   placeholder="<?php echo $this->__('To'); ?>"
									   value="<?php echo htmlspecialchars(isset($filter->value['to']) ? $filter->value['to'] : ''); ?>" />
							</div>
						</div>
						<?php else: ?>
						<input class="form-control" type="text"
							   name="<?php echo htmlspecialchars('filter['.$filter->id.']'); ?>"
							   value="<?php echo htmlspecialchars(isset($filter->value) ? $filter->value : ''); ?>" />
						<?php endif; ?>
					</div>
					<?php endforeach; ?>
				<?php endforeach; ?>
				</div>
			</div>
			<?php if (isset($hasTableView) && $hasTableView): ?>
			<div class="col-sm-2" style="text-align: right; padding-right: 0;">
				<label>&nbsp;</label><br/>
				<div style="text-align: right;">
					<?php if (isset($listControls) && $listControls) require $listControls; ?>
				</div>
			</div>
			<?php endif; ?>
		</div>
		<div class="row" style="margin-right: 0;">
			<div class="col-xs-12">
				<button class="btn btn-success" type="submit"><?php echo $this->__('Search'); ?></button>
			</div>
		</div>
	</form>
	<?php elseif (isset($hasTableView) && $hasTableView): ?>
	<div>
		<div style="text-align: right;">
			<?php if (isset($listControls) && $listControls) require $listControls; ?>
		</div>
	</div>
	<?php endif; ?>
</div>
<div class="wb-store-list"<?php if ($tableView): ?> style="margin-left: 10px; margin-right: 10px;"<?php endif; ?>>
<?php if ($tableView): ?>
	<table class="wb-store-table table table-condensed table-bordered table-striped" style="text-align: left;">
		<thead>
			<tr>
				<th><?php echo $this->__('Item Name'); ?></th>
				<th><?php echo $this->__('Price'); ?></th>
				<?php foreach ($tableFields as $field): ?>
				<th><?php echo $this->noPhp($field->name); ?></th>
				<?php endforeach; ?>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($items as $item): ?>
			<tr>
				<td>
					<a href="<?php echo htmlspecialchars($request->detailsUrl($item).$urlAnchor); ?>"
					   class="wb-store-table-name-link"
					   title="<?php echo htmlspecialchars(tr_($item->name)); ?>"><?php
						if (isset($item->image->image)):
						?><div style="background-image: url('<?php echo htmlspecialchars($item->image->image); ?>');"
							   title="<?php echo htmlspecialchars(tr_($item->name)); ?>"></div><?php
						elseif ($noPhotoImage):
						?><div style="background-image: url('<?php echo htmlspecialchars($noPhotoImage->thumb); ?>');"></div><?php
						else:
						?><span class="wb-store-nothumb glyphicon glyphicon-picture"></span><?php
						endif;
						?><span class="wb-store-table-name"><?php echo $this->noPhp(tr_($item->name)); ?></span>
					</a>
				</td>
				<td><?php echo $this->formatPrice($item->price); ?></td>
				<?php foreach ($this->tableFieldValues($tableFields, $item) as $field): ?>
				<td><?php echo (isset($field->value) ? $this->noPhp($field->value) : '&mdash;'); ?></td>
				<?php endforeach; ?>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php else: ?>	
	<?php foreach ($items as $item): ?>
	<div class="wb-store-item"
		 data-item-id="<?php echo htmlspecialchars($item->id); ?>"
		 onclick="location.href = '<?php echo htmlspecialchars($request->detailsUrl($item).$urlAnchor); ?>';">
		<div class="wb-store-thumb">
			<a href="<?php echo htmlspecialchars($request->detailsUrl($item).$urlAnchor); ?>">
			<?php if (isset($item->image->thumb)): ?>
			<img src="<?php echo htmlspecialchars($item->image->thumb); ?>"
				 alt="<?php echo htmlspecialchars(tr_($item->name)); ?>" />
			<?php elseif ($noPhotoImage): ?>
			<img src="<?php echo htmlspecialchars($noPhotoImage->thumb); ?>" alt="" />
			<?php else: ?>
			<span class="wb-store-nothumb glyphicon glyphicon-picture"></span>
			<?php endif; ?>
			</a>
		</div>
		<div class="wb-store-name">
			<a href="<?php echo htmlspecialchars($request->detailsUrl($item).$urlAnchor); ?>"><?php echo $this->noPhp(tr_($item->name)); ?></a>
		</div>
		<?php if ($item->price && $this->formatPrice($item->price)): ?>
			<div class="wb-store-price"><?php echo $this->formatPrice($item->price); ?></div>
		<?php endif; ?>
	</div>
	<?php endforeach; ?>
<?php endif; ?>
</div>
<?php if (isset($paging) && $paging && $paging->pageCount > 1): ?>
<?php
	$queryArray = array_merge(array(), $_GET);
	unset($queryArray['page']);
	unset($queryArray['cpp']);
	$qs = (count($queryArray)) ? '&'.http_build_query($queryArray) : '';
?>
<div class="wb-store-list">
	<ul class="pagination">
		<li<?php if ($paging->pageIndex == 0) echo ' class="disabled"'; ?>><a href="<?php echo $currBaseUrl; ?>?page=<?php echo max($paging->pageIndex, 1); ?>&amp;cpp=<?php echo $paging->itemsPerPage; ?><?php echo $qs.$urlAnchor; ?>">&laquo;</a></li>
		<?php if ($paging->startPageIndex > 0): ?>
		<li><a href="<?php echo $currBaseUrl; ?>?page=1&amp;cpp=<?php echo $paging->itemsPerPage; ?><?php echo $qs.$urlAnchor; ?>">1</a></li>
			<?php if ($paging->startPageIndex > 1): ?>
		<li class="disabled"><a href="javascript:void(0)">...</a></li>
			<?php endif; ?>
		<?php endif; ?>
		<?php for ($i = $paging->startPageIndex; $i <= $paging->endPageIndex; $i++): ?>
		<li<?php if ($paging->pageIndex == $i) { echo ' class="active"'; } ?>><a href="<?php echo $currBaseUrl; ?>?page=<?php echo $i + 1; ?>&amp;cpp=<?php echo $paging->itemsPerPage; ?><?php echo $qs.$urlAnchor; ?>"><?php echo $i + 1; ?></a></li>
		<?php endfor; ?>
		<?php if ($paging->endPageIndex < ($paging->pageCount - 1)): ?>
			<?php if ($paging->endPageIndex < ($paging->pageCount - 2)): ?>
		<li class="disabled"><a href="javascript:void(0)">...</a></li>
			<?php endif; ?>
		<li><a href="<?php echo $currBaseUrl; ?>?page=<?php echo $paging->pageCount; ?>&amp;cpp=<?php echo $paging->itemsPerPage; ?><?php echo $qs.$urlAnchor; ?>"><?php echo $paging->pageCount; ?></a></li>
		<?php endif; ?>
		<li<?php if ($paging->pageIndex == ($paging->pageCount - 1)) { echo ' class="disabled"'; } ?>><a href="<?php echo $currBaseUrl; ?>?page=<?php echo min($paging->pageCount, ($paging->pageIndex + 2)); ?>&amp;cpp=<?php echo $paging->itemsPerPage; ?><?php echo $qs.$urlAnchor; ?>">&raquo;</a></li>
	</ul>
</div>
<?php endif; ?>
<script type="text/javascript">
	$(function() { window.WBStoreModule.initStoreList('<?php echo $elementId; ?>', '<?php echo $cartUrl; ?>'); });
</script>
