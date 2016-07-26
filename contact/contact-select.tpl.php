<!-- Three panel selection -->

	<fieldset class="contact-select ui-widget ui-widget-content ui-corner-all">
		<legend><?= t('Contacts'); ?></legend>

		<div class="col-1">
			<div class="contact-groups ui-corner-left ui-widget-header">
				<?php if($favorites) : ?>

					<ul class="ui-helper-reset ui-helper-clearfix">
						<li class="ui-state-default ui-corner-left" rel="Favorite">
							<div class="count"><?= count($favorites); ?></div>
							<div class="text"><?= t('Favorites'); ?><div>
						</li>
					</ul>
					<hr />
				<?php endif; ?>
				<ul class="ui-helper-reset ui-helper-clearfix contact-groups-classes">
					<?php
						$codes = Codes::arrayMap('P_CLASS');
						$codes = array_map('t', $codes);
					?>
					<?php foreach($groups as $groupID => $nr) : ?>
						<li class="ui-state-default ui-corner-left" rel="<?= check_plain($groupID) ?>">
							<div class="count"><?= $nr; ?></div>
							<div class="text"><?= $codes[$groupID] ?></div>
						</li>
					<?php endforeach; ?>
				</ul>
				<hr />
				<ul class="ui-helper-reset ui-helper-clearfix">
					<li class="ui-state-default ui-corner-left ui-state-active" rel=""><!-- Show all -->
						<div class="count"><?= count($contacts); ?></div>
						<div class="text"><?= t('All contacts'); ?><div>
					</li>
				</ul>
			</div>

		</div>
		<div class="col-2">

			<div class="contact-contacts ui-widget-header">
				<form class="contact-select-tool">
					<?= t('Select:'); ?>
					<button class="select-all" onclick="return false;"><?= t('All'); ?></button>
					<button class="select-none" onclick="return false;"><?= t('None'); ?></button>

				</form>
				<hr />
				<form name="contacts">
					<ul class="ui-helper-reset ui-helper-clearfix">
						<?php foreach($contacts as $contact) : ?>
							<?php
								$classes = (array) $contact->get('p_class');
								$_view = intra_api_view($contact);
								$uid = $_view->getUid();
								if(isset($favorites[$contact->get('id')])) $classes[] = 'Favorite';
							?>
							<li class="<?= implode(' ', $classes); ?> ui-state-default ui-corner-left">
								<?php $id = form_clean_id($uid); ?>
								<label for="<?= $id; ?>">
									<input type="checkbox" value="<?= $uid ?>" name="contact[]" id="<?=$id;?>" />
									<?= $_view; ?>
								</label>
							</li>
						<?php endforeach; ?>
					</ul>
				</form>
			</div>

		</div>
		<div class="col-3">

			<div class="contact-actions ui-corner-right ui-helper-reset ui-helper-clearfix ui-widget-header">
				<div class="toolbar" style="width:100%">
					<?php if($company) : ?>
						<form action="<?= url('intra/contact/add'); ?>" style="display:inline;">
							<button class="action-add" name="c_id" value="<?= $company->get('c_id'); ?>"><?= t('Add contact'); ?></button>
						</form>
					<?php endif; ?>
					<button class="action-mailto contact-action" onclick="return false;"><?= t('Compose mail'); ?></button>
					<button class="action-history contact-action" onclick="return false;"><?= t('History'); ?></button>
				</div>
			</div>

			<ul class="rolodex ui-corner-right ui-widget-content">
				<?php foreach($contacts as $contact) : ?>
					<li class="contact <?= intra_api_view($contact)->getUid(); ?>">
						<?= theme('intra_contact_details-contact_select', $contact); ?>
					</li>
				<?php endforeach; ?>
			</ul>
			<div class="ui-helper-clearfix" style="clear:both;"></div>
		</div>
		<div class="ui-helper-clearfix" style="clear:both;"></div>
	</fieldset>
<script>

/*
$(document).ready(function() {
	
	$('.contact-select').each(function() {


		$('.contact-groups-classes li', self).droppable({ // Make droppable
			accept: '.vcard.person', // Accept person vcards
			activeClass: 'ui-state-hover',
			hoverClass: 'ui-state-active',
			drop: function(event, ui) {
				alert("Saatu henkilö: "+ ui.draggable.text());
			}
		});


	});

	//$(".vcard.person").draggable({ helper: 'clone' });

});
*/
</script>
