<?php

function intra_contact_page_vcard($person) {

	$name = sprintf('%s_%s.vcf',$person->get('p_fname'), $person->get('p_lname'));
	if(module_exists('transliteration'))
		$name = module_invoke('transliteration', 'clean_filename', $name);
	else
		$name = preg_replace('/[^a-0-9A-Za-z_.-]+/', '', $name);

	drupal_set_header(sprintf('Content-Type: text/x-vcard; charset=utf-8; name=%s', $name));
#	drupal_set_header(sprintf('Content-Disposition: attachment; filename=%s', $name));

	$vcard = new Intra_View_VCard_Person($person);

	echo $vcard;
}
