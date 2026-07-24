<?php declare(strict_types = 1);

namespace Tests\Helpers;

use Tester\Assert;
use WebChemistry\Testing\Components\Hierarchy\DomQuery;

class FormAssert
{

	public static function domHas(DomQuery $domQuery, string $selector): void
	{
		Assert::true($domQuery->has($selector), sprintf('Element %s not found in DOM', $selector));
	}

	public static function domNotHas(DomQuery $domQuery, string $selector): void
	{
		Assert::false($domQuery->has($selector), sprintf('Element %s found in DOM', $selector));
	}

}
