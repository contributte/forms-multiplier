<?php

use WebChemistry\Forms\Controls\Multiplier;
use Nette\Forms\Container;
use Nette\Forms\Form;

class MultipleCreateButtonsTest extends \Codeception\TestCase\Test {

	public function testAddTwoContainers() {
		$control = $this->getControl()->addCreateButton(NULL, 2);

		$this->assertCount(3, $control->getControls());

		$control->onCreateSubmit(current($control->getCreateButtons()));

		$this->assertCount(7, $control->getControls());
	}

	public function testAddTwoCreateButtons() {
		$control = $this->getControl()->addCreateButton(NULL, 2)->addCreateButton();

		$this->assertCount(4, $control->getControls());

		$buttons = $control->getCreateButtons();

		$control->onCreateSubmit($buttons[1]);
		$this->assertCount(6, $control->getControls());

		$control->onCreateSubmit($buttons[2]);
		$this->assertCount(10, $control->getControls());
	}

	public function testCreateMaxCopies() {
		$control = $this->getControl(NULL, 1, 10)->addCreateButton(NULL, 20);

		$buttons = $control->getCreateButtons();

		$control->onCreateSubmit($buttons[20]);
		
		$this->assertCount(21, $control->getControls());
	}

	public function testSubmitCreateTwoContainers() {
		$form = $this->sendRequestToPresenter('multiplier', [
			'multiplier' => [
				Multiplier::SUBMIT_CREATE_NAME . '2' => 'submit'
			]
		], function (Form $form) {
			$this->attachToForm($form, function (Container $container) {
				$container->addText('test');
			}, 0)->addCreateButton(NULL, 2);
		});

		/** @var Multiplier $multiplier */
		$multiplier = $form['multiplier'];

		$this->assertCount(3, $multiplier->getControls());
	}

	public function testSubmitCreateTwoContainersWithDefaults() {
		$defaults = [
			['test' => 'val'],
			['test' => 'val']
		];
		$form = $this->sendRequestToPresenter('multiplier', [
			'multiplier' => [
				['test' => 'val'],
				['test' => 'val'],
				Multiplier::SUBMIT_CREATE_NAME . '2' => 'submit'
			]
		], function (Form $form) use ($defaults) {
			$this->attachToForm($form, function (Container $container) {
				$container->addText('test');
			}, 0)->addCreateButton(NULL, 2)->setDefaults($defaults);
		});

		/** @var Multiplier $multiplier */
		$multiplier = $form['multiplier'];

		$this->assertCount(5, $multiplier->getControls());
	}

	/************************* Helpers **************************/

	protected function attachToForm(\Nette\Forms\Form $form, $factory, $copyNumber = 1, $maxCopies = NULL) {
		return $form['multiplier'] = new Multiplier($factory, $copyNumber, $maxCopies);
	}

	/**
	 * @return Multiplier
	 */
	protected function getControl($factory = NULL, $copyNumber = 1, $maxCopies = NULL) {
		$form = new \Nette\Forms\Form();

		if ($factory === NULL) {
			$factory = function (Container $container) {
				$container->addText('first');
				$container->addText('second');
			};
		}

		return $form['multiplier'] = new Multiplier($factory, $copyNumber, $maxCopies);
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
