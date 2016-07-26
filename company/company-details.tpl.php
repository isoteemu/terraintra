<div class="vcard company details <?= ($visible) ? 'node-published' : 'node-unpublished'; ?>" data-uid="<?= $view->getUid(); ?>">

	<fieldset>
		<?php if(!$visible) : ?>
			<div class="visibility-notice ui-widget-header ui-corner-all">
				<?= t('This company is hidden, and will not be shown in searches.'); ?>
			</div>
		<?php endif; ?>

		<form action="<?= url(intra_api_url($company).'/edit'); ?> " method="get" class="sosialism">
			<?= $view->get('c_logo')->thumbnail(); ?><br />
			<input type="hidden" name="destination" value="<?= $_GET['q']; ?>">
			<button><?= t('Modify'); ?></button>
		</form>

		<dl class="compact" style="width:100%;">
			<dt><?= t('Company'); ?></dt>
			<dd>
				<?= $view->get('c_cname'); ?>
				<?php if($company->get('c_oldname') && $company->get('c_oldname') != $company->get('c_cname')) : ?>
					(<?= $view->get('c_oldname');?>)
				<?php endif; ?>
				<?php if($group) : ?>
					, <em><?= t('a !group Company', array('!group' => (string) $group)); ?></em>
				<?php endif; ?>
			</dd>

			<dt><?= t('Taxonomy'); ?></dt>
			<dd>
				<?= $view->get('c_class'); ?>
			</dd>

			<dt><?= t('Location');?></dt>
			<dd>
				<div class="adr">
					<?php if($company->get('c_city')) { ?>
						<?= $view->get('c_city'); ?>,
					<?php } ?>
					<?= $view->get('c_country'); ?>
					<?= $view->get('c_location'); ?>
				</div>
			</dd>

			<dt><?= t('Since'); ?></dt>
			<dd>
				<?= $view->get('c_regdate'); ?>
				<?php if($company->get('prospect_by')) : ?>
					<?= t('by'); ?>
					<?= $view->get('prospect_by'); ?>
				<?php endif; ?>
			</dd>

			<?php if($contact = $view->getContact()) : ?>
				<dt><?= t('Contact'); ?></dt>
				<dd><?= $contact ?></dd>
			<?php endif; ?>
		</dl>

	</fieldset>

	<div id="company-detailed" class="tabs">
		<?php
			$remarks = ($company->get('c_rem')) ? true : false;
		?>
		<ul class="nav">
			<li><a href="#details"><?= t('Company Details'); ?></a></li>
			<li><a href="#billing"><?= t('Billing info'); ?></a></li>
			<?php if($subsidiarys) : ?>
				<li><a href="#subsidiarys"><?= t('Subsidiarys'); ?></a></li>
			<?php endif; ?>
			<?php if($sales) : ?>
				<li><a href="#sales"><?= t('Sale Performance'); ?></a></li>
			<?php endif; ?>
			<?php if($remarks) : ?>
				<li><a href="#rem"><?= t('Remarks'); ?></a></li>
			<?php endif; ?>
		</ul>

		<fieldset id="details">
			<legend><?= t('Company Details'); ?></legend>

			<dl class="company-details-damp">
				<dt><?= t('Type'); ?></dt>
				<dd><?= $view->get('c_type'); ?></dd>

				<dt><?= t('Website') ?></dt>
				<dd><?= $view->get('c_url'); ?></dd>

				<dt><?= t('Email'); ?></dt>
				<dd><?= $view->get('c_email'); ?></dd>

				<dt><?= t('Phone'); ?></dt>
				<dd><?= $view->get('c_phone'); ?></dd>

				<dt><?= t('Fax'); ?></dt>
				<dd><?= $view->get('c_telefax'); ?></dd>

				<dt><?= t('Comments'); ?></dt>
				<dd><?= $view->get('c_rem'); ?></dd>
			</dl>

			<dl class="company-details-addr addr">
				<?php if(!($manager = $view->getManager())) : ?>
					<?php $manager = t('None'); ?>
				<?php endif; ?>

				<dt><?= t('Manager'); ?></dt>
				<dd><?= $manager ?></dd>

				<dt><?= t('P.O. Box'); ?></dt>
				<dd><?= $view->get('c_box'); ?></dd>

				<dt><?= t('Street') ?></dt>
				<dd><?= $view->get('c_street'); ?></dd>

				<dt><?= t('Zip'); ?></dt>
				<dd><?= $view->get('c_zip'); ?></dd>

				<dt><?= t('City'); ?></dt>
				<dd><?= $view->get('c_city'); ?></dd>

				<dt><?= t('Country'); ?></dt>
				<dd><?= $view->get('c_country'); ?></dd>
			</dl>
		</fieldset>

		<fieldset id="billing">
			<legend><?= t('Billing info');?></legend>

			<?php if(!($accountist = $view->getAccountist())) : ?>
				<?php $accountist = false; ?>
			<?php endif; ?>

			<dl class="company-details-bill">
				<dt><?= t('Accountists'); ?></dt>
				<dd><?= ($accountist) ? $accountist : t('None'); ?></dd>

				<dt><?= t('Vat ID') ?></dt>
				<dd><?= $view->get('c_vat'); ?></dd>

				<dt><?= t('IBAN'); ?></dt>
				<dd>STUB</dd>

				<dt><?= t('Discount'); ?></dt>
				<dd><?= $view->get('c_discount'); ?>%</dd>
			</dl>

			<?php if($accountist) : ?>
				<dl class="company-details-bill-addr">
					<dt><?= t('P.O. Box'); ?></dt>
					<dd><?= $accountist->get('p_box'); ?></dd>

					<dt><?= t('Street') ?></dt>
					<dd><?= $accountist->get('p_street'); ?></dd>

					<dt><?= t('Zip'); ?></dt>
					<dd><?= $accountist->get('p_zip'); ?></dd>

					<dt><?= t('City'); ?></dt>
					<dd><?= $accountist->get('p_city'); ?></dd>

					<dt><?= t('Country'); ?></dt>
					<dd><?= $accountist->get('p_country'); ?></dd>
				</dl>
			<?php endif; ?>

		</fieldset>

		<?php if($subsidiarys) : ?>
			<fieldset id="subsidiarys">
				<legend><?= t('Subsidiarys');?></legend>
				<?= theme('item_list', $subsidiarys); ?>
			</fieldset>
		<?php endif; ?>

		<?php if($remarks) : ?>
			<fieldset id="rem">
				<legend><?= t('Remarks'); ?></legend>
				<?= $view->getRemarks(); ?>
			</fieldset>
		<?php endif; ?>
	</div>

	<div>
		<?= theme('intra_contact_select', $company->people(), $company); ?>
	</div>
</div>
<script type="text/javascript">
	$('.tabs').tabs();
</script>