<div class="company vcard details infobox" data-uid="<?= $view->getUid(); ?>">
	<fieldset class="details">
		<legend><?= $view; ?></legend>
		<form action="<?= url(intra_api_url($company).'/edit'); ?> " method="get" class="sosialism" onsubmit="return Intra.EditDestination(this);">
			<?= $view->get('c_logo')->thumbnail(); ?><br />
			<input type="hidden" name="destination" value="<?= $_GET['q']; ?>">
			<button><?= t('Modify'); ?></button>
		</form>
		<div class="data-container">
			<dl class="compact">
				<dt><?= t('Taxonomy'); ?></dt>
				<dd><?= $view->get('c_class'); ?></dd>
			</dl>

			<dl class="adr">
				<dt><?= t('Location');?></dt>
				<dd><?= $view->get('c_city'); ?></dd>

				<dt><?= t('Country'); ?></dt>
				<dd><?= $view->get('c_country'); ?></dd>
			</dl>

		</div>
	</fieldset>
	<?php foreach($sections as $content) : ?>
		<?= theme('intra_infobox_section', $content); ?>
	<?php endforeach; ?>

</div>
