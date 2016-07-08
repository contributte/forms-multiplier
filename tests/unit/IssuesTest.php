<?php

use WebChemistry\Forms\Controls\Multiplier;
use Nette\Forms\Container;
use Nette\Forms\Form;

class IssuesTest extends \Codeception\TestCase\Test {

	// thx to foxycode #2
	public function testZeroCopies() {
		$multiplier = $this->getControl(NULL, 0)->addRemoveButton()->addCreateButton();

		$this->assertCount(0, $multiplier->getContainers());
		$this->assertNotEmpty($multiplier->getCreateButtons());

		// send request to presenter
		$form = $this->sendRequestToPresenter('multiplier', [
			'multiplier' => [
				Multiplier::SUBMIT_CREATE_NAME => 'submit'
			]
		], function (\Nette\Forms\Form $form) {
			$this->attachToForm($form, function (Container $container) {
				$container->addText('test');
			}, 0)->addCreateButton()->addRemoveButton();
		});
		
		/** @var Multiplier $multiplier */
		$multiplier = $form['multiplier'];

		$containers = iterator_to_array($multiplier->getContainers());
		$this->assertCount(1, $containers);

		$this->assertNotEmpty($multiplier->getCreateButtons());
		$this->assertNotNull($containers[0]->getComponent(Multiplier::SUBMIT_REMOVE_NAME, FALSE));
	}

	// thx to foxycode #2
	public function testConditionOn() {
		$form = $this->sendRequestToPresenter('multiplier', [
			'brand' => 'filled',
			'multiplier' => [
				['test' => ''],
				Multiplier::SUBMIT_CREATE_NAME => 'submit'
			]
		], function (\Nette\Forms\Form $form) {
			$form->addText('brand');
			$this->attachToForm($form, function (Container $container, Form $form) {
				$container->addText('test')
					->addConditionOn($form['brand'], $form::FILLED)
						->setRequired();
			}, 1)->addCreateButton()->addRemoveButton();
		});

		$this->assertTrue($form->hasErrors());
	}

	// thx to foxycode #2
	public function testDefaults() {
		$defaults = [
			[
				'name'  => 'root_authorized_keys',
				'value' => 'test',
			],
			[
				'name'  => 'user-script',
				'value' => 'test2',
			],
		];
		$factory = function (\Nette\Forms\Form $form) use ($defaults) {
			$this->attachToForm($form, function (Container $container) {
				$container->addText('name', 'Name');
				$container->addText('value', 'Value');
			}, 0)->addCreateButton()->addRemoveButton()->setDefaults($defaults);
		};

		$defaults[0][Multiplier::SUBMIT_REMOVE_NAME] = 'submit';
		$form = $this->sendRequestToPresenter('multiplier', [
			'multiplier' => $defaults
		], $factory);

		/** @var Multiplier $multiplier */
		$multiplier = $form['multiplier'];

		$this->assertCount(1, $multiplier->getContainers());
		unset($defaults[0]);

		// Remove submit second
		$defaults[1][Multiplier::SUBMIT_REMOVE_NAME] = 'submit';
		$form = $this->sendRequestToPresenter('multiplier', [
			'multiplier' => $defaults
		], $factory);

		/** @var Multiplier $multiplier */
		$multiplier = $form['multiplier'];

		$this->assertCount(0, $multiplier->getContainers());

		// 0 - containers => add copy
		$form = $this->sendRequestToPresenter('multiplier', [
			'multiplier' => [
				Multiplier::SUBMIT_CREATE_NAME => 'submit'
			]
		], $factory);

		/** @var Multiplier $multiplier */
		$multiplier = $form['multiplier'];

		$this->assertCount(1, $multiplier->getContainers());
	}

	/************************* Helpers **************************/

	protected function attachToForm(\Nette\Forms\Form $form, $factory, $copyNumber = 1, $maxCopies = NULL, $createForce = FALSE) {
		return $form['multiplier'] = new Multiplier($factory, $copyNumber, $maxCopies, $createForce);
	}
	
	/**
	 * @return Multiplier
	 */
	protected function getControl($factory = NULL, $copyNumber = 1, $maxCopies = NULL, $createForce = FALSE) {
		$form = new \Nette\Forms\Form();

		if ($factory === NULL) {
			$factory = function (Container $container) {
				$container->addText('first');
				$container->addText('second');
			};
		}

		return $form['multiplier'] = new Multiplier($factory, $copyNumber, $maxCopies, $createForce);
	}

	/**
	 * @param string $name
	 * @return \Nette\Application\UI\Presenter
	 */
	protected function createPresenter($name) {
		$presenterFactory = new \Nette\Application\PresenterFactory(function ($class) {
			/** @var \Nette\Application\UI\Presenter $presenter */
			$presenter = new $class();
			$presenter->injectPrimary(NULL, NULL, NULL,
				new \Nette\Http\Request(new \Nette\Http\UrlScript()), new \Nette\Http\Response(), NULL, NULL,
				new MockLatte());
			$presenter->autoCanonicalize = FALSE;

			return $presenter;
		});

		return $presenterFactory->createPresenter($name);
	}

	protected function sendRequestToPresenter($controlName = 'multiplier', $post, $factory = NULL) {
		$presenter = $this->createPresenter('Multiplier');
		if (is_callable($factory)) {
			$factory($presenter->getForm());
		}
		$presenter->run(new \Nette\Application\Request('Multiplier', 'POST', [
			'do' => $controlName . '-submit'
		], $post));
		/** @var \Nette\Application\UI\Form $form */
		$form = $presenter[$controlName];

		return $form;
	}

}
