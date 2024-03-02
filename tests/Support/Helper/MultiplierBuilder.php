<?php declare(strict_types = 1);

namespace Tests\Support\Helper;

use Nette\Application\UI\Form;
use Nette\Forms\Container;
use Nette\SmartObject;
use Contributte\FormMultiplier\Multiplier;

class MultiplierBuilder
{

	use SmartObject;

	/** @var array */
	public $fields = [
		'bar' => '',
	];

	/** @var callable[] */
	protected $formModifiers = [];

	/** @var callable[] */
	protected $beforeFormModifiers = [];

	/** @var callable[] */
	protected $multiplierModifiers = [];

	/** @var callable[] */
	protected $containerModifiers = [];

	/** @var array */
	protected $multiplierArgs = [];

	public function __construct(int $copyNumber = 1, ?int $maxCopies = null)
	{
		$this->multiplierArgs = [$copyNumber, $maxCopies];
	}

	public function factory(Container $container): void
	{
		foreach ($this->fields as $field => $value) {
			$container->addText($field)
				->setDefaultValue($value);
		}

		foreach ($this->containerModifiers as $modifier) {
			$modifier($container);
		}
	}

	public function fields(array $fields): self
	{
		$this->fields = $fields;

		return $this;
	}

	public function addRemoveButton(?callable $onCreate = null): self
	{
		$this->multiplierModifiers[] = function (Multiplier $multiplier) use ($onCreate) {
			$btn = $multiplier->addRemoveButton('add');
			if ($onCreate) {
				$btn->addOnCreateCallback($onCreate);
			}
		};

		return $this;
	}

	public function addCreateButton(int $copyCount = 1, ?callable $onCreate = null): self
	{
		$this->multiplierModifiers[] = function (Multiplier $multiplier) use ($copyCount, $onCreate) {
			$btn = $multiplier->addCreateButton('add', $copyCount);
			if ($onCreate) {
				$btn->addOnCreateCallback($onCreate);
			}
		};

		return $this;
	}

	public static function create(int $copyNumber = 1, ?int $maxCopies = null): self
	{
		return new self($copyNumber, $maxCopies);
	}

	public function setMinCopies(int $minCopies): self
	{
		$this->multiplierModifiers[] = function (Multiplier $multiplier) use ($minCopies) {
			$multiplier->setMinCopies($minCopies);
		};

		return $this;
	}

	public function createForm(): Form
	{
		$form = new Form();

		foreach ($this->beforeFormModifiers as $modifier) {
			$modifier($form);
		}

		$form['m'] = $multiplier = new Multiplier([$this, 'factory'], ...$this->multiplierArgs);

		foreach ($this->multiplierModifiers as $modifier) {
			$modifier($multiplier);
		}

		foreach ($this->formModifiers as $modifier) {
			$modifier($form);
		}

		$form->addSubmit('send');

		$form->onSuccess[] = function(Form $form) {};

		return $form;
	}

	public function formModifier(callable $callback): self
	{
		$this->formModifiers[] = $callback;

		return $this;
	}

	public function beforeFormModifier(callable $callback): self
	{
		$this->beforeFormModifiers[] = $callback;

		return $this;
	}

	public function multiplierModifier(callable $callback): self
	{
		$this->multiplierModifiers[] = $callback;

		return $this;
	}

	public function containerModifier(callable $callback): self
	{
		$this->containerModifiers[] = $callback;

		return $this;
	}

	public function setFormDefaults(array $defaults): self
	{
		$this->formModifiers[] = function (Form $form) use ($defaults) {
			$form->setDefaults($defaults);
		};

		return $this;
	}

}
