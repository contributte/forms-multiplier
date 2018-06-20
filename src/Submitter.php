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
	 * @return int
	 */
	public function getCopyCount() {
		return $this->copyCount;
	}

}
