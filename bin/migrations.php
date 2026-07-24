<?php declare(strict_types=1);

use Nextras\Dbal\Connection;
use Nextras\Migrations\Bridges\NextrasDbal\NextrasAdapter;
use Nextras\Migrations\Controllers\ConsoleController;
use Nextras\Migrations\Drivers\MySqlDriver;
use Nextras\Migrations\Extensions\SqlHandler;

require __DIR__ . '/../vendor/autoload.php';

$container = (new App\Bootstrap())->bootWebApplication();

/** @var array<string, mixed> $parameters */
$parameters = $container->getParameters();
/** @var array<string, mixed> $dbParameters */
$dbParameters = is_array($parameters['db'] ?? null) ? $parameters['db'] : [];

$connection = new Connection([
	'driver' => 'mysqli',
	'host' => (string) ($dbParameters['host'] ?? 'db'),
	'port' => (int) ($dbParameters['port'] ?? 3306),
	'database' => (string) ($dbParameters['name'] ?? 'db'),
	'username' => (string) ($dbParameters['user'] ?? 'db'),
	'password' => (string) ($dbParameters['password'] ?? 'db'),
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
