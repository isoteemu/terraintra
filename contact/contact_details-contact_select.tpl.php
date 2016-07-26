<fieldset>
	<legend><?= $view ?> <?= theme('intra_contact_vcard_download', $contact); ?></legend>
	<div class="vcard person" data-uid="<?= $view->getUid(); ?>">

		<form action="<?= url(intra_api_url($contact).'/edit'); ?> " method="get" class="sosialism">
			<?= $view->getPhoto()->thumbnail(); ?><br />
				<input type="hidden" name="destination" value="<?= $_GET['q']; ?>">
				<button><?= t('Modify'); ?></button>
		</form>

		<div class="data-container">
			<dl class="compact">
				<dt><?= t('Company'); ?></dt>
				<dd><?= $view->getCompany(); ?></dd>

				<dt><?= t('Title'); ?></dt>
				<dd><?= $view->get('p_title'); ?></dd>
			</dl>

			<dl class="contact">
				<dt><?= t('Email'); ?></dt>
				<dd><?= $view->get('p_email'); ?></dd>
			</dl>

			<dl class="tel">
				<dt><?= t('Phone'); ?></dt>
				<dd><?= $view->get('p_phone'); ?></dd>

				<dt><?= t('Fax'); ?></dt>
				<dd><?= $view->get('p_telefax'); ?></dd>
			</dl>
		</div>

		<div class="ui-helper-clearfix"></div>

		<fieldset class="collapsible collapsed data-container">
			<legend><?= t('Address'); ?></legend>
			<dl class="adr">

				<dt><?= t('P.O. Box'); ?></dt>
				<dd><?= $view->get('p_box'); ?></dd>

				<dt><?= t('Street'); ?></dt>
				<dd><?= $view->get('p_street'); ?></dd>

				<dt><?= t('Zip'); ?></dt>
				<dd><?= $view->get('p_zip'); ?></dd>

				<dt><?= t('City'); ?></dt>
				<dd><?= $view->get('p_city'); ?></dd>

				<dt><?= t('Country'); ?></dt>
				<dd><?= $view->get('p_country'); ?></dd>
			</dl>
		</fieldset>

		<div class="ui-helper-clearfix"></div>
		<?php if($contact->get('p_rem')) : ?>
			<fieldset class="collapsible collapsed data-container">
				<legend><?= t('Notes'); ?></legend>
				<?= $view->get('p_rem'); ?>
			</fiedlset>
		<?php endif; ?>

	</div>
</fieldset>
