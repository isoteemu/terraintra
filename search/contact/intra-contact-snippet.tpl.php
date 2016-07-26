<div class="vcard person details" data-uid="<?= $view->getUid(); ?>">
	<?= $view->get('p_photo')->thumbnail(); ?>
	<dl>
		<dt><?= t('Contact');?></dt>
		<dd>
			<?= $view->getFn(); ?>
		</dd>

		<dt><?= t('Company');?></dt>
		<dd>
			<?= $view->getCompany(); // It's like magic! ?>
			<?php if($contact->get('p_dname')) : ?>
				, <?= $view->get('p_dname'); ?>
			<?php endif; ?>
		</dd>

		<dt><?= t('Title');?></dt>
		<dd>
			<?= $view->get('p_title'); ?>
		</dd>

		<dt><?= t('Taxonomy'); ?></dt>
		<dd>
			<?= $view->get('p_class'); ?>
		</dd>

<!--
		<?php if($contact->get('p_email')) : ?>
			<dt><?= t('Email') ?></dt>
			<dd><?= $view->get('p_email'); ?></dd>
		<?php endif; ?>
-->
	</dl>
	<div style="clear:both;"><!-- float anchor, baby yeah! --></div>
</div>
