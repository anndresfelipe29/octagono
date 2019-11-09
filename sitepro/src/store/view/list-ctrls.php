<div class="dropdown" style="display: inline-block; margin-right: 10px;"
	 title="<?php echo htmlspecialchars($this->__('Sort')); ?>">
	<button type="button" id="store_sorter_dp"
			class="btn btn-default btn-sm dropdown-toggle"
			data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
		<?php echo (isset($sortingFuncList[$sorting]) ? $this->__($sortingFuncList[$sorting]) : $this->__('Sort')); ?>
		<span class="caret"></span>
	</button>
	<ul class="dropdown-menu dropdown-menu-right" aria-labelledby="store_sorter_dp">
		<?php foreach ($sortingFuncList as $k => $v): ?>
		<li<?php if ($k == $sorting) echo ' class="active"'; ?>>
			<a href="<?php echo htmlspecialchars(str_replace('__SORT__', $k, $sortingUrl)); ?>"><?php echo $this->__($v); ?></a>
		</li>
		<?php endforeach; ?>
	</ul>
</div>
<a href="<?php echo htmlspecialchars($thumbViewUrl); ?>"
   class="btn btn-default btn-sm<?php if (!$tableView) echo ' active'; ?>">
	<span class="glyphicon glyphicon-th"></span>
</a>
<a href="<?php echo htmlspecialchars($tableViewUrl); ?>"
   class="btn btn-default btn-sm<?php if ($tableView) echo ' active'; ?>">
	<span class="glyphicon glyphicon-list"></span>
</a>
