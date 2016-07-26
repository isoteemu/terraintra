<div class="ui-dialog ui-widget ui-widget-content ui-corner-all invoices">
	<h3 class="ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix">
		<a name="Invoices" class="ui-dialog-title"><?= t('Invoices');?></a>
	</h3>

	<?php
		$thead = array(
			array(
				'data' => t('Number'),
				'field' => 'in_nr',
				'header' => true
			),
			array(
				'data' => t('PO'),
				'field' => 'in_cust_reference'
			),
			array(
				'data' => t('Date'),
				'field' => 'in_invoice_date',
				'sort' => 'desc'
			),
			array(
				'data' => t('Type'),
				'field' => 'in_type'
			),
			t('End customer')
		);
		$tablesort = tablesort_init($thead);

		$invoices->sortChildren($tablesort['sql'], ($tablesort['sort'] == 'asc') ? SORT_ASC : SORT_DESC);

		foreach($invoices as $invoice) {
			$in_view = intra_api_view($invoice);

			$rows[] = array(
				array('data' => (string) $in_view, 'header' => true),
				(string) $in_view->get('in_cust_reference'),
				(string) $in_view->get('in_invoice_date'),
				(string) $in_view->get('in_type'),
				(string) $in_view->customer()
			);
		}

	?>

	<div id="invoice-list">
		<?= theme('table', $thead, $rows); ?>
	</div> <!-- /  id="invoice-list" -->
</div>
