<?php

return [
	'goutteClient' => [
		'class' => 'Goutte\Client',
	],

	'worldjournal' => [
		'class' => 'app\models\scrape\WJModel',
		'_hostname' => 'http://www.wjlife.com',
		'_currentCatId' => 327, //restaurant help, hardcoded in their js code
		'_currentCatName' => 'restaurant help',
		'_currentStateId' => 152,
		'_currentRegionName' => "state_ny",
		'_wjlang' => "zh-cn",
		'_pageSize' => 100, // fetch ad counts per ajax call
		// '_client' =>  [
		// 	'class' => 'Goutte\Client'
		// ],
		// '_crawler' => [
		// 	'class' => 'Symfony\Component\DomCrawler\Crawler'
		// ],
	],
	'craigslist' => [
		'class' => 'app\models\scrape\CLModel',
		'_hostname' => 'http://newyork.craigslist.org/',
		'_sectionName' => 'software',
		'_sectionEndpoint' => 'search/sof',
		'_linkCount' => 3,
	],

];
