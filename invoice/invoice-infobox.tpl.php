<div class="invoice details infobox" data-uid="<?= $view->getUid(); ?>">
	<fieldset class="details">
		<legend><?= $view->get('in_nr'); ?>: <?= $view->billing(); ?></legend>
		<form action="<?= url(intra_api_url($invoice).'/edit'); ?> " method="get" class="sosialism" onsubmit="return Intra.EditDestination(this);">
			<?= $view->billing()->get('c_logo')->thumbnail(); ?><br />
			<input type="hidden" name="destination" value="<?= $_GET['q']; ?>">
			<button><?= t('Modify'); ?></button>
		</form>
		<div class="data-container">
			<dl class="compact">

				<dt><?= t('Type'); ?></dt>
				<dd><?= $view->get('in_type'); ?></dd>

				<?php if($invoice->billing()->current() != $invoice->customer()->current()) : ?>
					<dt><?= t('Customer'); ?></dt>
					<dd><?= $view->customer(); ?></dd>
				<?php endif; ?>

				<dt><?= t('Fee'); ?></dt>
				<dd><?= $view->get('in_fee'); ?></dd>

				<?php if(!$invoice->get('in_payment_date')) : ?>
					<dt><?= t('Payment'); ?></dt>
					<dd><?= $view->get('in_payment_date'); ?></dd>
				<?php endif; ?>

				<?php if($invoice->get('x_file')) : ?>
					<dt><?= t('Attachment'); ?></dt>
					<dd><?= $view->get('x_file'); ?></dd>
				<?php endif; ?>

			</dl>
		</div>
	</fieldset>
	<?php foreach($sections as $content) : ?>
		<?= theme('intra_infobox_section', $content); ?>
	<?php endforeach; ?>

</div>
