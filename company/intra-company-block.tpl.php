<?php
$id = form_clean_id('company-infobox-block');
?>
<aside id="<?= $id ?>" class="company vcard details infobox">
	<?php foreach($sections as $section) : ?>
		<?php // Fuck this ?>
		<h4><a><?= strip_tags($section['#title']); ?></a></h4>
		<div>
			<?= $section['#value']; ?>
		</div>
	<?php endforeach; ?>
</aside>
<script type="text/javascript">
$("#<?= $id; ?>").accordion({
	header: 'h4'
});
</script>