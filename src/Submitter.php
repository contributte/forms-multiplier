<?php declare(strict_types = 1);

namespace Contributte\FormMultiplier;

use Nette\Forms\Controls\SubmitButton;

final class Submitter extends SubmitButton implements ISubmitter
{

	/** @var int */
	private $copyCount = 1;

	public function __construct(?string $caption, int $copyCount = 1)
	{
		parent::__construct($caption);

		$this->copyCount = $copyCount;
	}

	public function getCopyCount(): int
	{
		return $this->copyCount;
	}

}
