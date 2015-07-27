<?php

namespace App\Presenters;

use Nette\Application\UI\Presenter;
use WebChemistry\Forms\Form;

class MultiplierPresenter extends Presenter {

	public function renderDefault() {
		$this->terminate();
	}

	/**
	 * Generates URL to presenter, action or signal.
	 *
	 * @param  string   destination in format "[//] [[[module:]presenter:]action | signal! | this] [#fragment]"
	 * @param  array|mixed
	 * @return string
	 * @throws InvalidLinkException
	 */
	public function link($destination, $args = array()) {
		return 'link';
	}

	protected function createComponentBase() {
		$form = new Form();

		return $form;
	}

	protected function createComponentDefaults() {
		$form = new Form();

		$multiplier = $form->addMultiplier('multiplier', function (\WebChemistry\Forms\Container $container) {
			$container->addText('first');
			$container->addText('second');
		}, 10);

		$multiplier->setDefaults(array(
			0 => array(
				'first' => 'First',
				'second' => 'Second'
			),
			1 => array(
				'first' => 'First 2',
				'second' => 'Second 2'
			)
		));

		return $form;
	}

	protected function createComponentDefaultValue() {
		$form = new Form;

		$multiplier = $form->addMultiplier('multiplier', function (\WebChemistry\Forms\Container $container) {
			$container->addText('first')
					  ->setDefaultValue('Value');
			$container->addText('second');
		}, 2);

		return $form;
	}

	protected function createComponentGetDefaultValue() {
		$form = new Form;

		$multiplier = $form->addMultiplier('multiplier', function (\WebChemistry\Forms\Container $container) {
			$container->addText('first')
					  ->setDefaultValue('Value');
			$container->addText('second');
		}, 2);

		return $form;
	}

	protected function createComponentForce() {
		$form = new Form;

		$multiplier = $form->addMultiplier('multiplier', function (\WebChemistry\Forms\Container $container) {
			$container->addText('first')
					  ->setDefaultValue('Value');
			$container->addText('second');
		}, 1, NULL, TRUE);

		$multiplier->setDefaults(array(
			0 => array(
				'first' => 'First',
				'second' => 'Second'
			),
			1 => array(
				'first' => 'First 2',
				'second' => 'Second 2'
			)
		));

		return $form;
	}

	protected function createComponentMaxCopies() {
		$form = new Form;

		$multiplier = $form->addMultiplier('multiplier', function (\WebChemistry\Forms\Container $container) {
			$container->addText('first')
					  ->setDefaultValue('Value');
			$container->addText('second');
		}, 10, 3, TRUE);

		$multiplier->setDefaults(array(
			0 => array(
				'first' => 'First',
				'second' => 'Second'
			),
			1 => array(
				'first' => 'First 2',
				'second' => 'Second 2'
			)
		));

		return $form;
	}

	protected function createComponentButtons() {
		$form = new Form;

		/** @var \WebChemistry\Forms\Controls\Multiplier $multiplier */
		$multiplier = $form->addMultiplier('multiplier', function (\WebChemistry\Forms\Container $container) {
			$container->addText('first');
			$container->addText('second');
		}, 2);

		$multiplier->addCreateSubmit();
		$multiplier->addRemoveSubmit();

		return $form;
	}

	protected function createComponentButtonsWithoutRemove() {
		$form = new Form;

		/** @var \WebChemistry\Forms\Controls\Multiplier $multiplier */
		$multiplier = $form->addMultiplier('multiplier', function (\WebChemistry\Forms\Container $container) {
			$container->addText('first');
			$container->addText('second');
		}, 1);

		$multiplier->addCreateSubmit();
		$multiplier->addRemoveSubmit();

		return $form;
	}

	protected function createComponentButtonsWithoutCreate() {
		$form = new Form;

		/** @var \WebChemistry\Forms\Controls\Multiplier $multiplier */
		$multiplier = $form->addMultiplier('multiplier', function (\WebChemistry\Forms\Container $container) {
			$container->addText('first');
			$container->addText('second');
		}, 5, 5);

		$multiplier->addCreateSubmit();
		$multiplier->addRemoveSubmit();

		return $form;
	}
}