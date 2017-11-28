<?php

use Nette\Forms\Container;
use WebChemistry\Forms\Controls\Multiplier;
use Nette\Application\UI\Form;
use WebChemistry\Testing\TUnitTest;

class MultiplierTest extends \Codeception\TestCase\Test {

	use TUnitTest;

	private function createMultiplier(callable $factory, $copyNumber = 1, $maxCopies = NULL) {
		$form = new Form();
		$form->addGroup('testGroup');

		$form['m'] = new Multiplier($factory, $copyNumber, $maxCopies);

		$form->addSubmit('send');

		return $form;
	}

	protected function _before() {
		$form = $this->services->form;

		$form->addForm('base', function ($copyNumber = 1, $maxCopies = NULL) {
			return $this->createMultiplier(function (Container $container) {
				$container->addText('bar');
			}, $copyNumber, $maxCopies);
		});

		$form->addForm('nested', function () {
			return $this->createMultiplier(function (Container $container) {
				$container->addText('bar');
				$container['m2'] = (new Multiplier(function (Container $container) {
					$container->addText('bar2');
				}))->addCreateButton('create');
			});
		});

		$form->addForm('buttons', function ($copyNumber = 1, $maxCopies = NULL, $minCopies = NULL) {
			$form = $this->createMultiplier(function (Container $container) {
				$container->addText('bar');
			}, $copyNumber, $maxCopies);

			if ($minCopies) {
				$form['m']->setMinCopies($minCopies);
			}

			$form['m']->addCreateButton('Add');
			$form['m']->addRemoveButton('Remove');

			return $form;
		});
	}

	public function testRenderBase() {
		$response = $this->services->form->createRequest('base')->render();

		$this->assertDomHas($response->toDomQuery(), 'input[name="m[0][bar]"]');
	}

	public function testSendBase() {
		$response = $this->services->form->createRequest('base')
			->setPost($params = [
				'm' => [
					['bar' => 'foo']
				]
			])->send();

		$this->assertTrue($response->isSuccess());
		$this->assertSame($params, $response->getValues());

		$dom = $response->toDomQuery();

		$this->assertDomHas($dom, 'input[name="m[0][bar]"][value="foo"]');
		$this->assertDomNotHas($dom, 'input[name="m[1][bar]"]');
	}

	public function testRenderCopy2() {
		$response = $this->services->form->createRequest('base', 2)->render();
		$dom = $response->toDomQuery();

		$this->assertDomHas($dom, 'input[name="m[0][bar]"]');
		$this->assertDomHas($dom, 'input[name="m[1][bar]"]');
	}

	public function testSendCopy2() {
		$response = $this->services->form->createRequest('base', 2)
			->setPost($params = [
				'm' => [
					['bar' => 'foo'],
					['bar' => 'bar'],
				]
			])->send();

		$this->assertSame($params, $response->getValues());

		$dom = $response->toDomQuery();

		$this->assertDomHas($dom, 'input[name="m[0][bar]"]');
		$this->assertDomHas($dom, 'input[name="m[1][bar]"]');
		$this->assertDomNotHas($dom, 'input[name="m[2][bar]"]');
	}

	public function testRenderMaxCopy() {
		$response = $this->services->form->createRequest('base', 2, 1)->render();
		$dom = $response->toDomQuery();

		$this->assertDomHas($dom, 'input[name="m[0][bar]"]');
		$this->assertDomNotHas($dom, 'input[name="m[1][bar]"]');
	}

	public function testSendMaxCopy() {
		$response = $this->services->form->createRequest('base', 2, 1)
			->setPost([
				'm' => [
					['bar' => 'foo'],
					['bar' => 'bar'],
				]
			])->send();

		$this->assertSame([
			'm' => [
				['bar' => 'foo'],
			]
		], $response->getValues());

		$dom = $response->toDomQuery();

		$this->assertDomHas($dom, 'input[name="m[0][bar]"]');
		$this->assertDomNotHas($dom, 'input[name="m[1][bar]"]');
	}

	public function testNested() {
		$request = $this->services->form->createRequest('nested');

		$dom = $request->render()->toDomQuery();
		$this->assertDomHas($dom, 'input[name="m[0][bar]"]');
		$this->assertDomHas($dom, 'input[name="m[0][m2][0][bar2]"]');
		$this->assertDomHas($dom, 'input[name="m[0][m2][' . Multiplier::SUBMIT_CREATE_NAME . ']"]');
	}

	public function testSendNested() {
		$request = $this->services->form->createRequest('nested');
		$request->setPost([
			'm' => [
				[
					'bar' => 'foo',
					'm2' => [
						['bar2' => 'xx']
					]
				],
				['bar' => 'bar'],
			]
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
					]
				],
				[
					'bar' => 'bar',
					'm2' => [],
				]
			],
		], $send->getValues());
	}

	public function testGroup() {
		$request = $this->services->form->createRequest('base');
		$dom = $request->render()->toDomQuery();
		$this->assertDomHas($dom, 'fieldset');
		$this->assertDomHas($dom, 'fieldset input[name="m[0][bar]"]');
	}

	public function testGroupManualRenderWithRemovedButtons() {
		$request = $this->services->form->createRequest('buttons', 2);
		$dom = $request->render()->toDomQuery();

		$this->assertDomNotHas($dom, 'input[name="m[0][multiplier_remover]"]');
		$this->assertDomNotHas($dom, 'input[name="m[1][multiplier_remover]"]');
	}

	public function testGroupManualRenderWithButtons() {
		$request = $this->services->form->createRequest('buttons', 2, NULL, 1);
		$dom = $request->render(__DIR__ . '/templates/group.latte')->toDomQuery();

		$this->assertDomHas($dom, 'input[name="m[0][multiplier_remover]"]');
		$this->assertDomHas($dom, 'input[name="m[1][multiplier_remover]"]');
	}

}
