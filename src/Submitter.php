<?php

namespace WebChemistry\Forms\Controls;

use Nette\Forms\Container;
use Nette\Forms\Controls\SubmitButton;

class Submitter extends SubmitButton implements ISubmitter {

	/** @var int */
	private $copyCount = 1;

	/** @var callable|null */
	private $onCreate;

	/**
	 * @param string $caption
	 * @param int $copyCount
	 * @param callable|null $onCreate
	 */
	public function __construct($caption, $copyCount = 1, callable $onCreate = null) {
		parent::__construct($caption);
		$this->copyCount = $copyCount;
		$this->onCreate = $onCreate;
	}

	protected function attached($form) {
		parent::attached($form);

		if ($form instanceof Container && $onCreate = $this->onCreate) {
			$onCreate($this);
		}
	}

	/**
	 * @param int $copyCount
	 * @return self
	 */
	public function setCopyCount($copyCount) {
		$this->copyCount = $copyCount;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getCopyCount() {
		return $this->copyCount;
	}

	/**
	 * @return string
	 */
	public function getOwnName() {
		return Multiplier::SUBMIT_CREATE_NAME . ($this->copyCount === 1 ? '' : $this->copyCount);
	}

}
