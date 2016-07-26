<div class="vcard company details" data-uid="<?= $view->getUid(); ?>">
	<?= $view->get('c_logo')->thumbnail(); ?>
	<dl>
		<dt><?= t('Company');?></dt>
		<dd>
			<?= $view->get('c_cname'); ?>

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
		<?php if($company->get('c_url')) : ?>
			<dt><?= t('Website') ?></dt>
			<dd><?= $view->get('c_url'); ?></dd>
		<?php endif; ?>
	</dl>
	<div style="clear:both;"><!-- float anchor, baby yeah! --></div>
</div>
