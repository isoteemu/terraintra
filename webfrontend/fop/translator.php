<?php
$file = dirname(__FILE__).'/invoice-fop.xsl';


function xsl_file_gettext($file, $_seen = array()) {
	if(isset($seen[$file])) return array();

	$_seen[$file] = true;
	$r = array();

	$dom = simplexml_load_file($file);

	foreach($dom->xpath('//xsl:call-template[@name="getText"]/xsl:with-param[@name]') as $t) {
		$str = trim($t['select'],"'");
		$r[] = $str;
	}

	foreach($dom->xpath('xsl:include[@href]') as $include) {
		$r += xsl_file_gettext((string) $include['href'], $_seen);
	}
	return array_unique($r);
}

print_r(translate_xsl_file($file));
