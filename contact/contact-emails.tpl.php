<div class="email-list">
	<?php foreach($emails as $email) : ?>
		<?= theme('intra_contact_email', $email); ?>
	<?php endforeach; ?>
</div>
