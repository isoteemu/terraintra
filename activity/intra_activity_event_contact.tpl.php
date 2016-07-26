<div style="display: table-cell; width:32px;" class="<?= ($direction == Intra_Activity_Event::EVENT_TO) ? 'ui-corner-left' : 'ui-corner-right';?> ui-state-default contact">
	<dt style="width:32px; height:32px;" class="vcard person" data-uid="<?= $view->getUid(); ?>">
		<a href="<?= url(intra_api_url($contact)); ?>" class="url">
			<?= $view->get('p_photo')->iconLarge()->addClass('fn')->setAttribute('alt', (string) $view->getFn());?>
		</a>
	</dt>
</div>
