<?php

namespace WebChemistry\Forms\Controls;

use Nette\Forms\Controls\SubmitButton;

class Submitter extends SubmitButton implements ISubmitter {

	/** @var int */
	private $copyCount = 1;

	/**
	 * @param string $caption
	 * @param int $copyCount
	 */
	public function __construct($caption, $copyCount = 1) {
		parent::__construct($caption);
		$this->copyCount = $copyCount;
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
