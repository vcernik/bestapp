<?php declare(strict_types=1);

use App\Bootstrap;
use App\Model\Orm\Orm;
use Nette\DI\Container;

require __DIR__ . '/../vendor/autoload.php';

Tester\Environment::setup();
Tester\Environment::setupFunctions();

function testContainer(): Container
{
	static $container = null;

	if ($container instanceof Container) {
		return $container;
	}

	$bootstrap = new Bootstrap;
	$container = $bootstrap->bootWebApplication();

	return $container;
}

function testOrm(): Orm
{
	return testContainer()->getByType(Orm::class);
}