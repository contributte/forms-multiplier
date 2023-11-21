<?php declare(strict_types = 1);

namespace Contributte\FormMultiplier\Buttons;

use Contributte\FormMultiplier\Multiplier;
use Nette\Forms\Controls\SubmitButton;
use Nette\SmartObject;
use Nette\Utils\Html;

final class RemoveButton
{

	use SmartObject;

	/** @var callable[] */
	public array $onCreate = [];

	private Html|string|null $caption = null;

	/** @var string[] */
	private array $classes = [];

	public function __construct(Html|string|null $caption)
	{
		$this->caption = $caption;
	}

	public function addOnCreateCallback(callable $onCreate): self
	{
		$this->onCreate[] = $onCreate;

		return $this;
	}

	public function addClass(string $class): self
	{
		$this->classes[] = $class;

		return $this;
	}

	public function create(Multiplier $multiplier): SubmitButton
	{
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
