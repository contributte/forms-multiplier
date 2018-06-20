<?php

namespace WebChemistry\Forms\Controls\Buttons;

use Nette\Forms\Container;
use Nette\Forms\Controls\SubmitButton;
use Nette\SmartObject;
use WebChemistry\Forms\Controls\Multiplier;

class RemoveButton {

	use SmartObject;

	/** @var null|string */
	private $caption;

	/** @var array */
	public $onCreate = [];

	/** @var array */
	private $classes = [];

	/**
	 * @param $caption string|null
	 */
	public function __construct($caption) {
		$this->caption = $caption;
	}

	/**
	 * @param callable $onCreate
	 * @return static
	 */
	public function addOnCreateCallback(callable $onCreate) {
		$this->onCreate[] = $onCreate;

		return $this;
	}

	/**
	 * @param string $class
	 * @return static
	 */
	public function addClass($class) {
		$this->classes[] = $class;

		return $this;
	}

	/**
	 * @param Multiplier $multiplier
	 * @return SubmitButton
	 */
	public function create(Multiplier $multiplier) {
		$button = new SubmitButton($this->caption);

		$button->setHtmlAttribute('class', implode(' ', $this->classes));
		$button->setValidationScope([])
			->setOmitted();

		$button->onClick[] = $button->onInvalidClick[] = [$multiplier, 'resetFormEvents'];

		foreach ($this->onCreate as $callback) {
			$callback($button);
		}

		return $button;
	}

}
