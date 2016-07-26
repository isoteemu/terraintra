<dialog class="activity">
	<?php foreach($events as $event) : ?>
		<?= theme('intra_activity_list_item', $event); ?>
	<?php endforeach; ?>
</dialog>
