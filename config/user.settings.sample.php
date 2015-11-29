<?php
// sample settings

$userSettings = [];

switch ( YII_ENV ) {
	case 'prod':
		$userSettings = [
			'db' => [
				'username' => 'root',
				'password' => 'root',
			],
		];
		break;
	case 'dev':
		$userSettings = [
			'db' => [
				'username' => 'root',
				'password' => 'root',
			],
		];
		break;
	default:
		break;
}

return $userSettings;
