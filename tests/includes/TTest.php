<?php

use PHPUnit\Framework\TestCase;
use WebChemistry\Testing\Components\Hierarchy\DomQuery;
use WebChemistry\Testing\Services;

/**
 * @mixin TestCase
 */
trait TTest
{

	/** @var Services */
	protected $services;

	public function setUp(): void
	{
		$this->services = new Services();

		$parent = get_parent_class($this);
		if ($parent !== FALSE && method_exists($parent, 'setUp')) {
			parent::setUp();
		}
	}

	public function assertThrownException(callable $function, string $class, ?string $message = NULL, $code = NULL): void
	{
		$this->addToAssertionCount(1);

		$e = NULL;
		try {
			call_user_func($function);
		} catch (\Exception $e) {
		}

		if ($e === NULL) {
			$this->fail("$class was expected, but none was thrown");
		} elseif (!$e instanceof $class) {
			$this->fail("$class was expected but got " . get_class($e) . ($e->getMessage() ? " ({$e->getMessage()})" : ''));
		} elseif ($message && $message !== $e->getMessage()) {
			$this->fail("$class with a message matching {$message} was expected but got {$e->getMessage()}");
		} elseif ($code !== NULL && $e->getCode() !== $code) {
			$this->fail("$class with a code {$code} was expected but got {$e->getCode()}");
		}
	}

	public function assertDomHas(DomQuery $domQuery, string $selector): void
	{
		$this->addToAssertionCount(1);

		if (!$domQuery->has($selector)) {
			$this->fail(sprintf('Element %s not found in DOM', $selector));
		}
	}

	public function assertDomNotHas(DomQuery $domQuery, string $selector): void
	{
		$this->addToAssertionCount(1);

		if ($domQuery->has($selector)) {
			$this->fail(sprintf('Element %s found in DOM', $selector));
		}
	}

}
