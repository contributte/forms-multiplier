<?php

use Nette\Forms\Container;
use Contributte\FormMultiplier\Multiplier;
use Nette\Application\UI\Form;
use WebChemistry\Testing\TUnitTest;

class DefaultValuesTest extends \Codeception\TestCase\Test
{

	use TUnitTest;

	/** @var array */
	private static $defaults = [
		'm' => [
			['bar' => 'foo'],
			['bar' => 'foo'],
		],
	];

	/** @var array */
	private static $defaultNested = [
		'm' => [
			[
				'bar' => 'foo1',
				'nested' => [
					['foo' => 'bar1'],
					['foo' => 'bar2'],
				],
			],
			[
				'bar' => 'foo2',
				'nested' => [
					['foo' => 'bar3'],
					['foo' => 'bar4'],
				],
			],
		],
	];

	public function testRender()
	{
		$response = $this->services->form->createRequest(
			MultiplierBuilder::create(2, null)
				->addCreateButton()
				->addRemoveButton()
				->setMinCopies(1)
				->setFormDefaults(self::$defaults)
				->createForm()
		)->render();

		$dom = $response->toDomQuery();

		$this->assertDomHas($dom, 'input[name="m[0][bar]"][value="foo"]');
		$this->assertDomHas($dom, 'input[name="m[1][bar]"][value="foo"]');
		$this->assertDomNotHas($dom, 'input[name="m[2][bar]"]');
	}

	public function testRenderAndSetDefaultsInAction()
	{
		$response = $this->services->form->createRequest(
			MultiplierBuilder::create()
				->createForm()
		)->setActionCallback(function (Form $form) {
			$form->setDefaults(self::$defaults);
		})->render();

		$dom = $response->toDomQuery();

		$this->assertDomHas($dom, 'input[name="m[0][bar]"][value="foo"]');
		$this->assertDomHas($dom, 'input[name="m[1][bar]"][value="foo"]');
		$this->assertDomNotHas($dom, 'input[name="m[2][bar]"]');
	}

	public function testRenderAndSetDefaultsInRender()
	{
		$response = $this->services->form->createRequest(
			MultiplierBuilder::create()
				->createForm()
		)->setRenderCallback(function (Form $form) {
			$form->setDefaults(self::$defaults);
		})->render();

		$dom = $response->toDomQuery();

		$this->assertDomHas($dom, 'input[name="m[0][bar]"][value="foo"]');
		$this->assertDomHas($dom, 'input[name="m[1][bar]"][value="foo"]');
		$this->assertDomNotHas($dom, 'input[name="m[2][bar]"]');
	}

	public function testRemoveButtons()
	{
		$response = $this->services->form->createRequest(
			MultiplierBuilder::create(2, null)
				->addCreateButton()
				->addRemoveButton()
				->setMinCopies(1)
				->setFormDefaults(self::$defaults)
				->createForm()
		)->render();

		$dom = $response->toDomQuery();
		$this->assertDomHas($dom, 'input[name="m[0][' . Multiplier::SUBMIT_REMOVE_NAME . ']"]');
		$this->assertDomHas($dom, 'input[name="m[1][' . Multiplier::SUBMIT_REMOVE_NAME . ']"]');
	}

	public function testDefaultValue()
	{
		$response = $this->services->form->createRequest(
			MultiplierBuilder::create(2, null)
				->addCreateButton()
				->addRemoveButton()
				->setMinCopies(1)
				->setFormDefaults(self::$defaults)
				->fields(['bar' => 'foo'])
				->createForm()
		)->render();

		$dom = $response->toDomQuery();
		$this->assertDomHas($dom, 'input[name="m[0][bar]"][value="foo"]');
	}

	public function testDefaultValueSend()
	{
		$response = $this->services->form->createRequest(
			MultiplierBuilder::create(2, null)
				->addCreateButton()
				->addRemoveButton()
				->setMinCopies(1)
				->setFormDefaults(self::$defaults)
				->fields(['bar' => 'foo'])
				->createForm()
		)->setPost([
			'm' => [
				['bar' => 'bar'],
				Multiplier::SUBMIT_CREATE_NAME => '',
			],
		])->send();

		$dom = $response->toDomQuery();
		$this->assertDomHas($dom, 'input[name="m[0][bar]"][value="bar"]');
		$this->assertDomHas($dom, 'input[name="m[1][bar]"][value="foo"]');
	}

	public function testNestedMultiplier()
	{
		$response = $this->services->form->createRequest(
			MultiplierBuilder::create(2, null)
				->containerModifier(function (Container $container) {
					$container['nested'] = new Multiplier(function (Container $container) {
						$container->addText('foo');
					});
				})
				->fields(['bar' => 'foo'])
				->setFormDefaults(self::$defaultNested)
				->createForm()
		)->render();

		$dom = $response->toDomQuery();
		$this->assertDomHas($dom, 'input[name="m[0][bar]"][value="foo1"]');
		$this->assertDomHas($dom, 'input[name="m[0][nested][0][foo]"][value="bar1"]');
		$this->assertDomHas($dom, 'input[name="m[0][nested][1][foo]"][value="bar2"]');
		$this->assertDomHas($dom, 'input[name="m[1][bar]"][value="foo2"]');
		$this->assertDomHas($dom, 'input[name="m[1][nested][0][foo]"][value="bar3"]');
		$this->assertDomHas($dom, 'input[name="m[1][nested][1][foo]"][value="bar4"]');
	}

}
