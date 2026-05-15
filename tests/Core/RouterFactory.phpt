<?php declare(strict_types=1);

use App\Core\RouterFactory;
use Nette\Http\Request;
use Nette\Http\UrlScript;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('admin route matches default presenter and action', function (): void {
	$router = RouterFactory::createRouter();
	$request = new Request(new UrlScript('http://localhost/admin', '/'));
	$params = $router->match($request);

	Assert::same('Admin:Home', $params['presenter'] ?? null);
	Assert::same('default', $params['action'] ?? null);
});


test('admin sign route matches public sign presenter', function (): void {
	$router = RouterFactory::createRouter();
	$request = new Request(new UrlScript('http://localhost/admin/sign/in', '/'));
	$params = $router->match($request);

	Assert::same('Admin:Public:Sign', $params['presenter'] ?? null);
	Assert::same('in', $params['action'] ?? null);
});


test('front route matches presenter action and id', function (): void {
	$router = RouterFactory::createRouter();
	$request = new Request(new UrlScript('http://localhost/article/detail/10', '/'));
	$params = $router->match($request);

	Assert::same('Front:Article', $params['presenter'] ?? null);
	Assert::same('detail', $params['action'] ?? null);
	Assert::same('10', $params['id'] ?? null);
});


test('admin presenter link is generated', function (): void {
	$router = RouterFactory::createRouter();
	$url = $router->constructUrl(
		[
			'presenter' => 'Admin:Home',
			'action' => 'default',
		],
		new UrlScript('http://localhost/', '/'),
	);

	Assert::same('http://localhost/admin/', $url);
});


test('admin public sign link is generated', function (): void {
	$router = RouterFactory::createRouter();
	$url = $router->constructUrl(
		[
			'presenter' => 'Admin:Public:Sign',
			'action' => 'in',
		],
		new UrlScript('http://localhost/', '/'),
	);

	Assert::same('http://localhost/admin/sign', $url);
});