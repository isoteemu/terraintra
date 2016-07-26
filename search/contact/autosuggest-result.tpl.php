<div class="vcard person details">
	<?= $view->get('p_photo')->iconMedium(); ?>
	<dl>
		<dt><?= t('Name');?></dt>
		<dd>
			<?= $view->getFn();?>
		</dd>
		<dt><?= t('Company');?></dt>
		<dd>
			<?= $view->getCompany(); ?>
			<?php if($contact->get('p_dname')) : ?>
				, <?= $view->get('p_dname'); ?>
			<?php endif; ?>
		</dd>
	</dl>
	<div style="clear:both;"><!-- float anchor, baby yeah! --></div>
</div>
