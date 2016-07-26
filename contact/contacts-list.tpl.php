<ul <?= drupal_attributes($attributes); ?>>
	<?php foreach($company->people() as $person) : ?>
		<?php $_view = intra_api_view($person); ?>
		<li class="vcard person" data-uid="<?= $_view->getUid(); ?>">
			<strong><?= $_view->getFn(); ?></strong><br />
			<?= $_view->get('p_title'); ?>
		</li>
	<?php endforeach; ?>
</ul>
