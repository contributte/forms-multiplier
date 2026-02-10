<?php declare(strict_types = 1);

namespace Tests\Helpers;

use Nette\Application\UI\Presenter;

class FooPresenter extends Presenter
{

	public function link(string $destination, mixed $args = []): string
	{
		return '';
	}

}
