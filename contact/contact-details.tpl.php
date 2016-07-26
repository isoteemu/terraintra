<div class="vcard person details" data-uid="<?= $view->getUid(); ?>">
	<fieldset>
		<form action="<?= url(intra_api_url($contact).'/edit'); ?> " method="get" class="sosialism">
			<?= $view->get('p_photo')->thumbnail(); ?><br />
			<input type="hidden" name="destination" value="<?= $_GET['q']; ?>" />
			<button><?= t('Modify'); ?></button>
		</form>


		<dl class="compact">
			<dt><?= t('Name'); ?></dt>
			<dd><?= $view->getFn(); ?></dd>

			<dt><?= t('Title'); ?></dt>
			<dd><?= $view->get('p_title'); ?></dd>

			<dt><?= t('Taxonomy'); ?></dt>
			<dd><?= $view->get('p_class'); ?></dd>

			<dt><?= t('Location');?></dt>
			<dd>
				<div class="adr">
					<?php if($contact->get('p_city')) { ?>
						<?= $view->get('p_city'); ?>,
					<?php } ?>
					<?= $view->get('p_country'); ?>
				</div>
			</dd>

			<dt><?= t('Company'); ?></dt>
			<dd>
				<?= $view->getCompany(); ?>
				<?php if($contact->get('p_dname')) : ?>
					, <?= $view->get('p_dname') ?>
				<?php endif; ?>

			</dd>
		</dl>
	</fieldset>

	<div id="contact-detailed" class="tabs">
		<ul class="nav">
			<li><a href="#details"><?= t('Contact Details'); ?></a></li>
			<li><a href="#remarks"><?= t('Remarks'); ?></a></li>
		</ul>

		<fieldset id="details">
			<legend><?= t('Contact Details'); ?></legend>

			<dl class="contact-details-damp">
				<dt><?= t('Type'); ?></dt>
				<dd><?= $view->get('p_type'); ?></dd>

				<dt><?= t('Website'); ?></dt>
				<dd><?= $view->getCompany()->get('c_url'); ?></dd>

				<dt><?= t('Email'); ?></dt>
				<dd>
					<?= theme('intra_contact_emails', $contact->getEmails()); ?>
				</dd>

				<dt><?= t('Skype'); ?></dt>
				<dd><?= $view->get('p_skype'); ?></dd>

				<?php if($contact->get('p_user')) : ?>
					<dt><?= t('Intra'); ?></dt>
					<dd><?= $view->get('p_user'); ?></dd>
				<?php endif; ?>
			</dl>


			<dl class="contact-details-tel">
				<dt><?= t('Phone'); ?></dt>
				<dd><?= $view->get('p_phone'); ?></dd>

				<dt><?= t('Fax'); ?></dt>
				<dd><?= $view->get('p_telefax'); ?></dd>
			</dl>

			<dl class="contact-details-addr addr">

				<dt><?= t('P.O. Box'); ?></dt>
				<dd><?= $view->get('p_box'); ?></dd>

				<dt><?= t('Street') ?></dt>
				<dd><?= $view->get('p_street'); ?></dd>

				<dt><?= t('Zip'); ?></dt>
				<dd><?= $view->get('p_zip'); ?></dd>

				<dt><?= t('City'); ?></dt>
				<dd><?= $view->get('p_city'); ?></dd>

				<dt><?= t('Country'); ?></dt>
				<dd><?= $view->get('p_country'); ?></dd>
			</dl>

		</fieldset>

		<fieldset id="remarks">
			<legend><?= t('Remarks'); ?></legend>
			<div><?= $view->get('p_rem'); ?></div>
		</fieldset>
	</div>
</div>
<script>
$(document).ready(function() {
	$(".tabs").tabs();
});
</script>

