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
		'_pageSize' => 80, // fetch ad counts per ajax call
		// if there are already _existedLinkCount number of newly crawled ad links existed in our db
		// we can say all ad links are crawled.
		'_existedLinkCount' => 10, 
	],

	'craigslist' => [
		'class' => 'app\models\scrape\CLModel',
		'_hostname' => 'http://newyork.craigslist.org',
		'_sectionName' => 'software',
		'_sectionEndpoint' => '/search/sof',
		'_linkCount' => 20,
		'_location' => 'new york city',
	],
];
