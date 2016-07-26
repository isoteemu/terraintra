<?php
$diff = time() - $event->getDate();
//2592000
$date = format_date($event->getDate());
?>

<time title="<?= t('@time ago', array('@time' => format_interval($diff))); ?>" datetime="<?= gmdate('c', $event->getDate()); ?>">
	<span class="date">
		<?= $date ?>
	</span>
</time>
<div class="actions">
	<?= implode(' | ', $event->getActions()) ?>
</div>
