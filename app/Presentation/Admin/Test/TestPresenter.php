<?php declare(strict_types=1);

namespace App\Presentation\Admin\Test;

use App\AdminCore\Presentation\Accessory\BasePrivatePresenter;
use App\AdminCore\Presentation\Accessory\DatagridFactory;
use App\Model\Orm\Article\ArticleRepository;
use Contributte\Datagrid\Datagrid;

final class TestPresenter extends BasePrivatePresenter
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
		$this->addBreadcrumb('Správa obsahu');
		$this->addBreadcrumb('Články');
	}

	protected function createComponentArticlesGrid(): Datagrid
	{
		$grid = $this->datagridFactory->createSortable('sortOrder', 'ASC');

		$grid->setDataSource($this->articleRepository->findAll());

		$grid->setDefaultSort(['sortOrder' => 'ASC']);

		$grid->addColumnNumber('sortOrder', 'Pořadí')
			->setSortable();		

		$grid->addColumnNumber('id', 'ID')
			->setSortable();

		$grid->addColumnText('title', 'Titulek')
			->setSortable()
			->setFilterText();

		$grid->addColumnDateTime('createdAt', 'Vytvoreno')
			->setFormat('j. n. Y H:i')
			->setSortable();

		return $grid;
	}

	public function handleSort(?int $item_id = null, ?int $prev_id = null, ?int $next_id = null): void
	{
		/** @var Datagrid $grid */
		$grid = $this['articlesGrid'];

		if ($item_id === null) {
			$grid->reload();
			return;
		}

		$reordered = $this->articleRepository->reorderByIds($item_id, $prev_id, $next_id);

		if (! $reordered) {
			$this->flashMessage('Článek se nepodařilo přesunout.', 'danger');
			$grid->reload();
			return;
		}

		$grid->reload();
	}

	public function renderDefault(): void
	{
	}
}
