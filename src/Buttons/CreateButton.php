<?php

namespace WebChemistry\Forms\Controls\Buttons;

use Nette\SmartObject;
use WebChemistry\Forms\Controls\Multiplier;
use WebChemistry\Forms\Controls\Submitter;

class CreateButton {

	use SmartObject;

	/** @var null|string */
	private $caption;

	/** @var int */
	private $copyCount;

	/** @var array */
	public $onCreate = [];

	/** @var array|null */
	private $validationScope = null;

	/** @var array */
	private $classes = [];
	
	/**
	 * @param $caption string|null
	 * @param $copyCount int
	 */
	public function __construct($caption, $copyCount = 1) {
		$this->caption = $caption;
		$this->copyCount = $copyCount;
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
	 * @return static
	 */
	public function setNoValidate() {
		$this->setValidationScope([]);

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
	 * @param array $validationScope
	 * @return static
	 */
	public function setValidationScope(array $validationScope) {
		$this->validationScope = $validationScope;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getComponentName() {
		return Multiplier::SUBMIT_CREATE_NAME . ($this->copyCount === 1 ? '' : $this->copyCount);
	}

	/**
	 * @return int
	 */
	public function getCopyCount() {
		return $this->copyCount;
	}

	/**
	 * @param Multiplier $multiplier
	 * @return Submitter
	 */
	public function create(Multiplier $multiplier) {
		$button = new Submitter($this->caption, $this->copyCount);

		$button->setHtmlAttribute('class', implode(' ', $this->classes));
		$button->setValidationScope($this->validationScope === null ? [$multiplier] : $this->validationScope)
			->setOmitted();

		foreach ($this->onCreate as $callback) {
			$callback($button);
		}

		$button->onClick[] = [$multiplier, 'resetFormEvents'];

		return $button;
	}

}
