<div>
	<dl class="agreement details-lite clear-block">
		<dt><?= t('Agreement'); ?></dt>
		<dd><?= $view; ?></dd>

		<dt><?= t('Status'); ?></dt>
		<dd><?= $view->get('ag_status'); ?></dd>
	</dl>

	<ul class="serials details-lite ">
		<?php foreach($serials as $serial) : ?>
			<li>
				<div class="count"><?= $serial['count']; ?></div>
				<div class="text"><?= $serial['name']; ?></div>
			</li>
		<?php endforeach; ?>
	</ul>
</div>