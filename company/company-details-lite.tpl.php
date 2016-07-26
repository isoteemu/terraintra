<span class="vcard company details details-lite" data-uid="<?= $view->getUid(); ?>">
	<?= $view->get('c_logo')->iconMedium(); ?>
	<dl>
		<dt><?= t('Company');?></dt>
		<dd>
			<?= $view->get('c_cname'); ?>
		</dd>
		<dt><?= t('Location');?></dt>
		<dd>
			<div class="adr">
				<?php if($company->get('c_city')) { ?>
					<?= $view->get('c_city'); ?>,
				<?php } ?>
				<?= $view->get('c_country'); ?>
			</div>
		</dd>
	</dl>
	<span style="clear:both;" class="ui-helper-clearfix"><!-- float anchor, baby yeah! --></span>
</span>
