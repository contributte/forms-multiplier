<?php declare(strict_types = 1);

namespace Tests\Cases\Unit;

use Contributte\FormMultiplier\Multiplier;
use Contributte\Tester\Toolkit;
use Nette\Application\UI\Form;
use Nette\Forms\Container;
use Tester\Assert;
use Tests\Helpers\FormAssert;
use Tests\Helpers\MultiplierBuilder;
use WebChemistry\Testing\Services;

require __DIR__ . '/../../bootstrap.php';

$services = new Services();

// testRenderBase
Toolkit::test(function () use ($services): void {
	$onCreateParams = [];
	$response = $services->form->createRequest(
		MultiplierBuilder::create()
			->beforeFormModifier(function (Form $form): void {
				$form->addGroup('testGroup');
			})
			->multiplierModifier(function (Multiplier $multiplier) use (&$onCreateParams): void {
				$multiplier->onCreate[] = function (Container $container) use (&$onCreateParams): void {
					$onCreateParams[] = $container;
				};
			})
			->createForm()
	)->render();

	FormAssert::domHas($response->toDomQuery(), 'input[name="m[0][bar]"]');
});

// testSendBase
Toolkit::test(function () use ($services): void {
	$response = $services->form->createRequest(
		MultiplierBuilder::create()
			->beforeFormModifier(function (Form $form): void {
				$form->addGroup('testGroup');
			})
			->multiplierModifier(function (Multiplier $multiplier): void {
				$multiplier->onCreate[] = function (Container $container): void {
				};
			})
			->createForm()
	)
		->setPost($params = [
			'm' => [
				['bar' => 'foo'],
			],
		])->send();

	Assert::true($response->isSuccess());
	Assert::same($params, $response->getValues());

	$dom = $response->toDomQuery();

	FormAssert::domHas($dom, 'input[name="m[0][bar]"][value="foo"]');
	FormAssert::domNotHas($dom, 'input[name="m[1][bar]"]');
});

// testRenderCopy2
Toolkit::test(function () use ($services): void {
	$response = $services->form->createRequest(
		MultiplierBuilder::create(2)
			->beforeFormModifier(function (Form $form): void {
				$form->addGroup('testGroup');
			})
			->multiplierModifier(function (Multiplier $multiplier): void {
				$multiplier->onCreate[] = function (Container $container): void {
				};
			})
			->createForm()
	)->render();
	$dom = $response->toDomQuery();

	FormAssert::domHas($dom, 'input[name="m[0][bar]"]');
	FormAssert::domHas($dom, 'input[name="m[1][bar]"]');
});

// testSendCopy2
Toolkit::test(function () use ($services): void {
	$response = $services->form->createRequest(
		MultiplierBuilder::create(2)
			->beforeFormModifier(function (Form $form): void {
				$form->addGroup('testGroup');
			})
			->multiplierModifier(function (Multiplier $multiplier): void {
				$multiplier->onCreate[] = function (Container $container): void {
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

	Assert::same($params, $response->getValues());

	$dom = $response->toDomQuery();

	FormAssert::domHas($dom, 'input[name="m[0][bar]"]');
	FormAssert::domHas($dom, 'input[name="m[1][bar]"]');
	FormAssert::domNotHas($dom, 'input[name="m[2][bar]"]');
});

// testRenderMaxCopy
Toolkit::test(function () use ($services): void {
	$response = $services->form->createRequest(
		MultiplierBuilder::create(2, 1)
			->beforeFormModifier(function (Form $form): void {
				$form->addGroup('testGroup');
			})
			->multiplierModifier(function (Multiplier $multiplier): void {
				$multiplier->onCreate[] = function (Container $container): void {
				};
			})
			->createForm()
	)->render();
	$dom = $response->toDomQuery();

	FormAssert::domHas($dom, 'input[name="m[0][bar]"]');
	FormAssert::domNotHas($dom, 'input[name="m[1][bar]"]');
});

// testSendMaxCopy
Toolkit::test(function () use ($services): void {
	$response = $services->form->createRequest(
		MultiplierBuilder::create(2, 1)
			->beforeFormModifier(function (Form $form): void {
				$form->addGroup('testGroup');
			})
			->multiplierModifier(function (Multiplier $multiplier): void {
				$multiplier->onCreate[] = function (Container $container): void {
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

	Assert::same([
		'm' => [
			['bar' => 'foo'],
		],
	], $response->getValues());

	$dom = $response->toDomQuery();

	FormAssert::domHas($dom, 'input[name="m[0][bar]"]');
	FormAssert::domNotHas($dom, 'input[name="m[1][bar]"]');
});

// testNested
Toolkit::test(function () use ($services): void {
	$request = $services->form->createRequest(
		MultiplierBuilder::create()
			->beforeFormModifier(function (Form $form): void {
				$form->addGroup('testGroup');
			})
			->multiplierModifier(function (Multiplier $multiplier): void {
				$multiplier->onCreate[] = function (Container $container): void {
				};
			})
			->containerModifier(function (Container $container): void {
				$container['m2'] = (new Multiplier(function (Container $container): void {
					$container->addText('bar2');
				}));
				$container['m2']->addCreateButton('create');
			})
			->createForm()
	);

	$dom = $request->render()->toDomQuery();
	FormAssert::domHas($dom, 'input[name="m[0][bar]"]');
	FormAssert::domHas($dom, 'input[name="m[0][m2][0][bar2]"]');
	FormAssert::domHas($dom, 'input[name="m[0][m2][' . Multiplier::SUBMIT_CREATE_NAME . ']"]');
});

// testSubmitFilter
Toolkit::test(function () use ($services): void {
	$response = $services->form->createRequest(
		MultiplierBuilder::create()
			->fields([])
			->beforeFormModifier(function (Form $form): void {
				$form->addInteger('num');
			})
			->multiplierModifier(function (Multiplier $multiplier): void {
				$multiplier->onCreate[] = function (Container $container): void {
					$container->addInteger('mnum');
				};
			})
			->formModifier(function (Form $form): void {
				$form->onSuccess[] = $form->onError[] = $form->onSubmit[] = function (): void {
				};
			})
			->createForm()
	)
		->setPost([
			'num' => '11',
			'm' => [
				['mnum' => '49'],
			],
		])->send();

	Assert::true($response->isSuccess());
	Assert::same([
		'num' => 11,
		'm' => [
			['mnum' => 49],
		],
	], $response->getValues());

	$dom = $response->toDomQuery();

	FormAssert::domHas($dom, 'input[name="m[0][mnum]"][value="49"]');
	FormAssert::domNotHas($dom, 'input[name="m[1][mnum]"]');
});

// testGroup
Toolkit::test(function () use ($services): void {
	$request = $services->form->createRequest(
		MultiplierBuilder::create()
			->beforeFormModifier(function (Form $form): void {
				$form->addGroup('testGroup');
			})
			->multiplierModifier(function (Multiplier $multiplier): void {
				$multiplier->onCreate[] = function (Container $container): void {
				};
			})
			->createForm()
	);
	$dom = $request->render()->toDomQuery();
	FormAssert::domHas($dom, 'fieldset');
	FormAssert::domHas($dom, 'fieldset input[name="m[0][bar]"]');
});

// testGroupManualRenderWithRemovedButtons
Toolkit::test(function () use ($services): void {
	$request = $services->form->createRequest(
		MultiplierBuilder::create(2)
			->beforeFormModifier(function (Form $form): void {
				$form->addGroup('testGroup');
			})
			->multiplierModifier(function (Multiplier $multiplier): void {
				$multiplier->onCreate[] = function (Container $container): void {
				};
				$multiplier->addCreateButton();
				$multiplier->addRemoveButton();
			})
			->createForm()
	);
	$dom = $request->render()->toDomQuery();

	FormAssert::domNotHas($dom, 'input[name="m[0][multiplier_remover]"]');
	FormAssert::domNotHas($dom, 'input[name="m[1][multiplier_remover]"]');
});

// testGroupManualRenderWithButtons
Toolkit::test(function () use ($services): void {
	$request = $services->form->createRequest(MultiplierBuilder::create(2)
		->beforeFormModifier(function (Form $form): void {
			$form->addGroup('testGroup');
		})
		->multiplierModifier(function (Multiplier $multiplier): void {
			$multiplier->onCreate[] = function (Container $container): void {
			};
			$multiplier->addCreateButton();
			$multiplier->addRemoveButton();
			$multiplier->setMinCopies(1);
		})
		->createForm());
	$dom = $request->render(__DIR__ . '/templates/group.latte')->toDomQuery();

	FormAssert::domHas($dom, 'input[name="m[0][multiplier_remover]"]');
	FormAssert::domHas($dom, 'input[name="m[1][multiplier_remover]"]');
});

// testOnCreateEvent
Toolkit::test(function () use ($services): void {
	$onCreateParams = [];
	$request = $services->form->createRequest(
		MultiplierBuilder::create()
			->beforeFormModifier(function (Form $form): void {
				$form->addGroup('testGroup');
			})
			->multiplierModifier(function (Multiplier $multiplier) use (&$onCreateParams): void {
				$multiplier->onCreate[] = function (Container $container) use (&$onCreateParams): void {
					$onCreateParams[] = $container;
				};
			})
			->createForm()
	)->modifyForm(function (Form $form): void {
		$form['m']->setValues([
			['bar' => 'foo'],
			['bar' => 'foo2'],
		]);
	});
	$request->render()->toString();

	Assert::true(count($onCreateParams) > 0);
	$values = ['foo', 'foo2'];
	foreach ($onCreateParams as $i => $parameter) {
		Assert::type(Container::class, $parameter);
		Assert::same($values[$i], $parameter['bar']->getValue());
	}
});

// testAddDynamic
Toolkit::test(function () use ($services): void {
	$request = $services->form->createRequest(
		MultiplierBuilder::create()
			->beforeFormModifier(function (Form $form): void {
				$form->addGroup('testGroup');
			})
			->multiplierModifier(function (Multiplier $multiplier): void {
				$multiplier->onCreate[] = function (Container $container): void {
				};
			})
			->createForm()
	)->modifyForm(function (Form $form): void {
		$form['m']->onCreateComponents[] = function (Multiplier $multiplier): void {
			$multiplier->addCopy(99)['bar']->setHtmlAttribute('class', 'myClass');
		};
	});

	$dom = $request->render()->toDomQuery();

	FormAssert::domHas($dom, '[name="m[99][bar]"]');
	FormAssert::domHas($dom, 'input.myClass');
});

// testPromptSelect
Toolkit::test(function () use ($services): void {
	$response = $services->form->createRequest(
		MultiplierBuilder::create()
			->containerModifier(function (Container $container): void {
				$container->addSelect('select', null, ['foo' => 'foo'])
					->setPrompt('Select');
			})
			->addCreateButton()
			->createForm()
	)
		->setPost([
			'm' => [
				['select' => '', 'multiplier_creator' => ''],
			],
		])->send();

	Assert::true($response->isSuccess());
});
