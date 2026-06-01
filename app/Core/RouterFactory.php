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


		$adminRouter = $router->withModule('AdminCore');
		$adminRouter->addRoute('admin/core/sign[/<action>]', 'Public:Sign:in');
		$adminRouter->addRoute('admin/core/<presenter>/<action>[/<id>]', 'Home:default');

		$adminRouter = $router->withModule('Admin');
		$adminRouter->addRoute('admin/<presenter>/<action>[/<id>]', 'Home:default');


		$router->withModule('Front')
			->addRoute('<presenter>/<action>[/<id>]', 'Home:default');
		return $router;
	}
}
