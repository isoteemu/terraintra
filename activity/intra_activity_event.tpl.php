<div class="row <?= $zebra; ?>"
	onmouseover="$('> div', this).addClass('ui-state-hover'); $('.body', this).slideDown('slow');"
	onmouseout="$('> div', this).removeClass('ui-state-hover');"
>
	<?php if($direction & Intra_Activity_Event::EVENT_TO) : ?>
		<?= theme('intra_activity_event_contact', $event, $direction); ?>
	<?php endif; ?>

	<div class="ui-widget-content <?= ($direction == Intra_Activity_Event::EVENT_FROM) ? 'ui-corner-left' : ''; ?>">
		<!-- Main content -->
		<?php
			if($event instanceOf Intra_Activity_TaggedEvent) {
				echo theme('intra_activity_tags', $event->getTags());
			}

			if($body)
				$body = "<dd>$body</dd>";
			else
				$title = "<dd>$title</dd>";

			echo "<h7 class=\"title\">$title</h7>";
			echo "<div class=\"body\">$body</div>";
		?>
	</div>

	
	<?php if($body) : // Illuminati ?>
	
		<?php $image = theme('image', drupal_get_path('module', 'intra_activity').'/image/illuminati.gif', t('Hover to view body')); ?>
		<div class="ui-state-default illuminati">
			<script type="text/javascript">
				<!--//--><![CDATA[//><!--
				if (Drupal.jsEnabled) {
					document.writeln('<?= addslashes($image) ?>');
				}
				//--><!]]>
			</script>
		</div>
	<?php endif; ?>

	<div class="ui-state-default info <?= ($direction == Intra_Activity_Event::EVENT_TO) ? 'ui-corner-right' : ''; ?>">
		<?= theme('intra_activity_event_info', $event); ?>
	</div>

	<?php if($direction & Intra_Activity_Event::EVENT_FROM) : ?>
		<?= theme('intra_activity_event_contact', $event, $direction); ?>
	<?php endif; ?>

</div>
