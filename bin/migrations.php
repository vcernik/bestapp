<?php declare(strict_types=1);

use Nextras\Dbal\Connection;
use Nextras\Migrations\Bridges\NextrasDbal\NextrasAdapter;
use Nextras\Migrations\Controllers\ConsoleController;
use Nextras\Migrations\Drivers\MySqlDriver;
use Nextras\Migrations\Extensions\SqlHandler;

require __DIR__ . '/../vendor/autoload.php';

$connection = new Connection([
	'driver' => 'mysqli',
	'host' => getenv('DB_HOST') ?: 'db',
	'port' => (int) (getenv('DB_PORT') ?: 3306),
	'database' => getenv('DB_NAME') ?: 'db',
	'username' => getenv('DB_USER') ?: 'db',
	'password' => getenv('DB_PASSWORD') ?: 'db',
]);

$dbal = new NextrasAdapter($connection);
$driver = new MySqlDriver($dbal);
$controller = new ConsoleController($driver);

$baseDir = dirname(__DIR__) . '/migrations';
$controller->addGroup('structures', $baseDir . '/structures');
$controller->addGroup('basic-data', $baseDir . '/basic-data', ['structures']);
$controller->addGroup('dummy-data', $baseDir . '/dummy-data', ['basic-data']);
$controller->addExtension('sql', new SqlHandler($driver));

$controller->run();
