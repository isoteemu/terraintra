<?php

function intra_activity_page_company(Company $company) {
	$people = $company->people();
	if(!count($people)) {
		drupal_set_message(t('No contacts found'), 'error');
		return drupal_not_found();
	}

	return theme('intra_activity_events', intra_activity($company));
}


/**
 * @ingroup Theme
 */
function theme_intra_activity_tags($tags) {
	$r = array();
	foreach($tags as $tag) {
		$r[] = Intra_View_Microformat_Tag_Tag::factory(t($tag))->__toString();
	}
	if(count($r))
		return '<div class="tags">'.implode(',', $r).'</div>';
	return '';
}
