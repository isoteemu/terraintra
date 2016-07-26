<div class="vcard contact details infobox" data-uid="<?= $view->getUid(); ?>">
	<fieldset class="details">
		<legend><?= $view->getFn(); ?></legend>
		<form action="<?= url(intra_api_url($contact).'/edit'); ?> " method="get" class="sosialism" onsubmit="Intra.EditDestination(this);" unselectable="on">
			<?= $view->get('p_photo')->thumbnail(); ?><br />
			<input type="hidden" name="destination" value="<?= $_GET['q']; ?>" />
			<button><?= t('Modify'); ?></button>
		</form>
		<div class="data-container">
			<dl class="compact">
				<dt><?= t('Company'); ?></dt>
				<dd><?= $view->getCompany()->get('c_cname'); ?></dd>

				<dt><?= t('Title'); ?></dt>
				<dd><?= $view->get('p_title'); ?></dd>

				<dt><?= t('Taxonomy'); ?></dt>
				<dd><?= $view->get('p_class'); ?></dd>
			</dl>

			<dl class="contact">
				<dt><?= t('Email'); ?></dt>
				<dd><?= $view->get('p_email'); ?></dd>
			</dl>

			<dl class="tel">
				<dt><?= t('Phone'); ?></dt>
				<dd><?= $view->get('p_phone'); ?></dd>
			</dl>

		</div>

	</fieldset>

	<?php if($activity) : ?>
		<fieldset class="collapsible">
			<legend><?= t('Activity'); ?></legend>
			<div class="activities">
				<?= theme('intra_activity_list', $activity); ?>
			</div>
		</fieldset>
	<?php endif; ?>
</div>