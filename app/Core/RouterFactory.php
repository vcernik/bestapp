<?php declare(strict_types=1);

namespace App\Core;

use Nette;
use Nette\Application\Routers\RouteList;


final class RouterFactory
{
	use Nette\StaticClass;

	public static function createRouter(): RouteList
	{
		$router = new RouteList;

		$adminRouter = $router->withModule('Admin');
		$adminRouter->addRoute('admin/sign[/<action>]', 'Public:Sign:in');
		$adminRouter->addRoute('admin/forgot-password[/<action>]', 'Public:ForgotPassword:request');
		$adminRouter->addRoute('admin/<presenter>/<action>[/<id>]', 'Home:default');
		$adminRouter->addRoute('admin', 'Home:default');

		$router->withModule('Front')
			->addRoute('<presenter>/<action>[/<id>]', 'Home:default');
		return $router;
	}
}
