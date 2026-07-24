<?php declare(strict_types=1);

namespace App\Presentation\Admin\Home;

use App\AdminCore\Presentation\Accessory\BasePrivatePresenter;
use App\AdminCore\Presentation\Accessory\DatagridFactory;
use App\Model\Orm\Article\ArticleRepository;
use Contributte\Datagrid\Datagrid;

final class HomePresenter extends BasePrivatePresenter
{
	public function __construct(
		private readonly ArticleRepository $articleRepository,
		private readonly DatagridFactory $datagridFactory,
	)
	{
	}

	protected function startup(): void
	{
		parent::startup();
		$this->addBreadcrumb('Dashboard');
	}

	public function renderDefault(): void
	{
	}
}
