<?php

use WebChemistry\Forms\Controls\Multiplier;
use Nette\Forms\Container;
use Nette\Forms\Form;

class RemoveTest extends \Codeception\TestCase\Test {

	public function testDeleteBelowMinCopies() {
		$form = $this->sendRequestToPresenter('multiplier', [
			'multiplier' => [
				['test' => 'val'],
				[
					'test' => 'val',
					Multiplier::SUBMIT_REMOVE_NAME => 'submit'
				]
			]
		], function (Form $form) {
			$this->attachToForm($form, function (Container $container) {
				$container->addText('test');
			}, 2)->addRemoveButton();
		});

		/** @var Multiplier $multiplier */
		$multiplier = $form['multiplier'];

		$this->assertCount(2, $multiplier->getContainers());
	}

	public function testDeleteMinCopies() {
		$form = $this->sendRequestToPresenter('multiplier', [
			'multiplier' => [
				['test' => 'val'],
				[
					'test' => 'val',
					Multiplier::SUBMIT_REMOVE_NAME => 'submit'
				]
			]
		], function (Form $form) {
			$this->attachToForm($form, function (Container $container) {
				$container->addText('test');
			}, 2)->addRemoveButton()->setMinCopies(1);
		});

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
