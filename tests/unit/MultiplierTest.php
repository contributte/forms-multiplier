<?php

use Nette\Forms\Container;
use Contributte\FormMultiplier\Multiplier;
use Nette\Application\UI\Form;

class MultiplierTest extends \Codeception\TestCase\Test
{

	use TTest;

	/** @var array */
	protected $parameters = [
		'onCreate' => [],
	];

	public function testRenderBase()
	{
		$response = $this->services->form->createRequest(
			MultiplierBuilder::create()
				->beforeFormModifier(function (Form $form) {
					$form->addGroup('testGroup');
				})
				->multiplierModifier(function (Multiplier $multiplier) {
					$multiplier->onCreate[] = function (Container $container) {
						$this->parameters['onCreate'][] = $container;
					};
				})
				->createForm()
		)->render();

		$this->assertDomHas($response->toDomQuery(), 'input[name="m[0][bar]"]');
	}

	public function testSendBase()
	{
		$response = $this->services->form->createRequest(
			MultiplierBuilder::create()
				->beforeFormModifier(function (Form $form) {
					$form->addGroup('testGroup');
				})
				->multiplierModifier(function (Multiplier $multiplier) {
					$multiplier->onCreate[] = function (Container $container) {
						$this->parameters['onCreate'][] = $container;
					};
				})
				->createForm()
		)
			->setPost($params = [
				'm' => [
					['bar' => 'foo'],
				],
			])->send();

		$this->assertTrue($response->isSuccess());
		$this->assertSame($params, $response->getValues());

		$dom = $response->toDomQuery();

		$this->assertDomHas($dom, 'input[name="m[0][bar]"][value="foo"]');
		$this->assertDomNotHas($dom, 'input[name="m[1][bar]"]');
	}

	public function testRenderCopy2()
	{
		$response = $this->services->form->createRequest(
			MultiplierBuilder::create(2)
				->beforeFormModifier(function (Form $form) {
					$form->addGroup('testGroup');
				})
				->multiplierModifier(function (Multiplier $multiplier) {
					$multiplier->onCreate[] = function (Container $container) {
						$this->parameters['onCreate'][] = $container;
					};
				})
				->createForm()
		)->render();
		$dom = $response->toDomQuery();

		$this->assertDomHas($dom, 'input[name="m[0][bar]"]');
		$this->assertDomHas($dom, 'input[name="m[1][bar]"]');
	}

	public function testSendCopy2()
	{
		$response = $this->services->form->createRequest(
			MultiplierBuilder::create(2)
				->beforeFormModifier(function (Form $form) {
					$form->addGroup('testGroup');
				})
				->multiplierModifier(function (Multiplier $multiplier) {
					$multiplier->onCreate[] = function (Container $container) {
						$this->parameters['onCreate'][] = $container;
					};
				})
				->createForm()
		)
			->setPost($params = [
				'm' => [
					['bar' => 'foo'],
					['bar' => 'bar'],
				],
			])->send();

		$this->assertSame($params, $response->getValues());

		$dom = $response->toDomQuery();

		$this->assertDomHas($dom, 'input[name="m[0][bar]"]');
		$this->assertDomHas($dom, 'input[name="m[1][bar]"]');
		$this->assertDomNotHas($dom, 'input[name="m[2][bar]"]');
	}

	public function testRenderMaxCopy()
	{
		$response = $this->services->form->createRequest(
			MultiplierBuilder::create(2, 1)
				->beforeFormModifier(function (Form $form) {
					$form->addGroup('testGroup');
				})
				->multiplierModifier(function (Multiplier $multiplier) {
					$multiplier->onCreate[] = function (Container $container) {
						$this->parameters['onCreate'][] = $container;
					};
				})
				->createForm()
		)->render();
		$dom = $response->toDomQuery();

		$this->assertDomHas($dom, 'input[name="m[0][bar]"]');
		$this->assertDomNotHas($dom, 'input[name="m[1][bar]"]');
	}

	public function testSendMaxCopy()
	{
		$response = $this->services->form->createRequest(
			MultiplierBuilder::create(2, 1)
				->beforeFormModifier(function (Form $form) {
					$form->addGroup('testGroup');
				})
				->multiplierModifier(function (Multiplier $multiplier) {
					$multiplier->onCreate[] = function (Container $container) {
						$this->parameters['onCreate'][] = $container;
					};
				})
				->createForm()
		)
			->setPost([
				'm' => [
					['bar' => 'foo'],
					['bar' => 'bar'],
				],
			])->send();

		$this->assertSame([
			'm' => [
				['bar' => 'foo'],
			],
		], $response->getValues());

		$dom = $response->toDomQuery();

		$this->assertDomHas($dom, 'input[name="m[0][bar]"]');
		$this->assertDomNotHas($dom, 'input[name="m[1][bar]"]');
	}

	public function testNested()
	{
		$request = $this->services->form->createRequest(
			MultiplierBuilder::create()
				->beforeFormModifier(function (Form $form) {
					$form->addGroup('testGroup');
				})
				->multiplierModifier(function (Multiplier $multiplier) {
					$multiplier->onCreate[] = function (Container $container) {
						$this->parameters['onCreate'][] = $container;
					};
				})
				->containerModifier(function (Container $container) {
					$container['m2'] = (new Multiplier(function (Container $container) {
						$container->addText('bar2');
					}));
					$container['m2']->addCreateButton('create');
				})
				->createForm()
		);

		$dom = $request->render()->toDomQuery();
		$this->assertDomHas($dom, 'input[name="m[0][bar]"]');
		$this->assertDomHas($dom, 'input[name="m[0][m2][0][bar2]"]');
		$this->assertDomHas($dom, 'input[name="m[0][m2][' . Multiplier::SUBMIT_CREATE_NAME . ']"]');
	}

	public function testSendNested()
	{
		$request = $this->services->form->createRequest(
			MultiplierBuilder::create()
				->beforeFormModifier(function (Form $form) {
					$form->addGroup('testGroup');
				})
				->multiplierModifier(function (Multiplier $multiplier) {
					$multiplier->onCreate[] = function (Container $container) {
						$this->parameters['onCreate'][] = $container;
					};
				})
				->containerModifier(function (Container $container) {
					$container['m2'] = (new Multiplier(function (Container $container) {
						$container->addText('bar2');
					}));
					$container['m2']->addCreateButton('create');
				})
				->createForm()
		);
		$request->setPost([
			'm' => [
				[
					'bar' => 'foo',
					'm2' => [
						['bar2' => 'xx'],
					],
				],
				['bar' => 'bar'],
				Multiplier::SUBMIT_CREATE_NAME => '',
			],
		]);

		$send = $request->send();
		$dom = $send->toDomQuery();
		$this->assertDomHas($dom, 'input[name="m[0][bar]"]');
		$this->assertDomHas($dom, 'input[name="m[0][m2][0][bar2]"]');

		$this->assertSame([
			'm' => [
				[
					'bar' => 'foo',
					'm2' => [
						['bar2' => 'xx'],
					],
				],
				[
					'bar' => 'bar',
					'm2' => [],
				],
				[
					'bar' => '',
					'm2' => [],
				],
			],
		], $send->getValues());
	}

	public function testGroup()
	{
		$request = $this->services->form->createRequest(
			MultiplierBuilder::create()
				->beforeFormModifier(function (Form $form) {
					$form->addGroup('testGroup');
				})
				->multiplierModifier(function (Multiplier $multiplier) {
					$multiplier->onCreate[] = function (Container $container) {
						$this->parameters['onCreate'][] = $container;
					};
				})
				->createForm()
		);
		$dom = $request->render()->toDomQuery();
		$this->assertDomHas($dom, 'fieldset');
		$this->assertDomHas($dom, 'fieldset input[name="m[0][bar]"]');
	}

	public function testGroupManualRenderWithRemovedButtons()
	{
		$request = $this->services->form->createRequest(
			MultiplierBuilder::create(2)
				->beforeFormModifier(function (Form $form) {
					$form->addGroup('testGroup');
				})
				->multiplierModifier(function (Multiplier $multiplier) {
					$multiplier->onCreate[] = function (Container $container) {
						$this->parameters['onCreate'][] = $container;
					};
					$multiplier->addCreateButton();
					$multiplier->addRemoveButton();
				})
				->createForm()
		);
		$dom = $request->render()->toDomQuery();

		$this->assertDomNotHas($dom, 'input[name="m[0][multiplier_remover]"]');
		$this->assertDomNotHas($dom, 'input[name="m[1][multiplier_remover]"]');
	}

	public function testGroupManualRenderWithButtons()
	{
		$request = $this->services->form->createRequest(MultiplierBuilder::create(2)
			->beforeFormModifier(function (Form $form) {
				$form->addGroup('testGroup');
			})
			->multiplierModifier(function (Multiplier $multiplier) {
				$multiplier->onCreate[] = function (Container $container) {
					$this->parameters['onCreate'][] = $container;
				};
				$multiplier->addCreateButton();
				$multiplier->addRemoveButton();
				$multiplier->setMinCopies(1);
			})
			->createForm());
		$dom = $request->render(__DIR__ . '/templates/group.latte')->toDomQuery();

		$this->assertDomHas($dom, 'input[name="m[0][multiplier_remover]"]');
		$this->assertDomHas($dom, 'input[name="m[1][multiplier_remover]"]');
	}

	public function testOnCreateEvent()
	{
		$this->assertEmpty($this->parameters['onCreate']);
		$request = $this->services->form->createRequest(
			MultiplierBuilder::create()
				->beforeFormModifier(function (Form $form) {
					$form->addGroup('testGroup');
				})
				->multiplierModifier(function (Multiplier $multiplier) {
					$multiplier->onCreate[] = function (Container $container) {
						$this->parameters['onCreate'][] = $container;
					};
				})
				->createForm()
		)->modifyForm(function (Form $form) {
			$form['m']->setValues([
				['bar' => 'foo'],
				['bar' => 'foo2'],
			]);
		});
		$request->render()->toString();

		$this->assertNotEmpty($this->parameters['onCreate']);
		$values = ['foo', 'foo2'];
		foreach ($this->parameters['onCreate'] as $i => $parameter) {
			$this->assertInstanceOf(Container::class, $parameter);
			$this->assertSame($values[$i], $parameter['bar']->getValue());
		}
	}

	public function testAddDynamic()
	{
		$request = $this->services->form->createRequest(
			MultiplierBuilder::create()
				->beforeFormModifier(function (Form $form) {
					$form->addGroup('testGroup');
				})
				->multiplierModifier(function (Multiplier $multiplier) {
					$multiplier->onCreate[] = function (Container $container) {
						$this->parameters['onCreate'][] = $container;
					};
				})
				->createForm()
		)->modifyForm(function (Form $form) {
			$form['m']->onCreateComponents[] = function (Multiplier $multiplier) {
				$multiplier->addCopy(99)['bar']->setHtmlAttribute('class', 'myClass');
			};
		});

		$dom = $request->render()->toDomQuery();

		$this->assertDomHas($dom, '[name="m[99][bar]"]');
		$this->assertDomHas($dom, 'input.myClass');
	}

	public function testPromptSelect()
	{
		$response = $this->services->form->createRequest(
			MultiplierBuilder::create()
				->containerModifier(function (Container $container) {
					$container->addSelect('select', null, ['foo' => 'foo'])
						->setPrompt('Select');
				})
				->addCreateButton()
				->createForm()
		)
			->setPost($params = [
				'm' => [
					['select' => '', 'multiplier_creator' => ''],
				],
			])->send();

		$this->assertTrue($response->isSuccess());

		$dom = $response->toDomQuery();

	}

}
