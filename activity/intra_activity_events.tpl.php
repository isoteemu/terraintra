<?php
	$fid = form_clean_id('intra-activity');
?>
<fieldset class="intra-activity-events ui-corner-all" id="<?= $fid; ?>">
	<legend><?= t('Activity'); ?></legend>
	<div>
		<!-- LINKS -->
	</div>

	<dialog class="activity">
		<?php foreach($events as $event) : ?>
			<?= theme('intra_activity_event', $event); ?>
		<?php endforeach; ?>
	</dialog>
</fieldset>
<script>
$(document).ready(function() {
	$("#<?= $fid; ?> dialog .row").each(function() {
		var body = $('.body dd', this).html();
		if(body) {
			var target = $('.illuminati', this);
			if(target.length == 1) {
				target.css('cursor', 'help');
				Intra.Ui.Tooltip(target, {
					position: {
						corner: { target: 'centerMiddle', tooltip: 'rightMiddle' }
					},
					content:  body,
					style: {
						width: target.position().left,
						tip: 'rightMiddle',
					}
				});
			}
		};
	});
});
</script>