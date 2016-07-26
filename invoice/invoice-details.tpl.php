<div class="invoice details" data-uid="<?= $view->getUid(); ?>">
	<?php
	foreach($invoice->attributes() as $prop) {
		$rows[] = array(
			$prop,
			(string) $view->get($prop)
		);
	}
	echo theme('table', array(), $rows);

	$articleHead = array(
		array(
			'data' => t('#'),
			'field' => 'se_rownr',
			'header' => true,
		),
		array(
			'data' => t('Serial'),
			'field' => 'se_serial',
		),
		array(
			'data' => t('Product'),
			'field' => 'pr_id',
		),
		array(
			'data' => t('Type'),
			'field' => 'se_type',
		),
		t('End Customer'),
		t('Note')
	);
	$tablesort = tablesort_init($articleHead);

	$items = $invoice->articles();
	$items->sortChildren($tablesort['sql'], ($tablesort['sort'] == 'asc') ? SORT_ASC : SORT_DESC);

	foreach($items as $article) {
		$articleView = intra_api_view($article);
		$itemRows[] = array(
			$articleView->get('se_rownr'),
			$articleView->get('se_serial'),
			$articleView->get('pr_id'),
			$articleView->get('se_type'),
			$articleView->get('se_c_id'),
			$articleView->getNote()
		);
	}

	echo theme('table', $articleHead, $itemRows, array('class' => 'articles'));

	?>
</div>
