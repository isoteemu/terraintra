<div class="row <?= $zebra; ?>">
	<?php if($event->getDirection() & Intra_Activity_Event::EVENT_TO || $event->getDirection() & Intra_Activity_Event::EVENT_FROM) : ?> 
		<dt class="vcard person" data-uid="<?= $view->getUid(); ?>">
			<a href="<?= $view->Url(); ?>" class="url">
				<?= $view->get('p_photo')->iconLarge()->addClass('fn')->setAttribute('alt', (string) $view->getFn());?>
			</a>
		</dt>
	<?php endif; ?>
	<dd>
		<?= filter_xss($event->getTitle()); ?>
	</dd>

	<div class="info">
		<?= theme('intra_activity_event_info', $event); ?>
	</div>
</div>