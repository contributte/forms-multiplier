<?php declare(strict_types = 1);

namespace Contributte\FormMultiplier;

final class ComponentResolver
{

	private string|int|null $removeId = null;

	private bool $removeAction = false;

	private bool $createAction = false;

	private int $createNum = 1;

	/** @var mixed[] */
	private array $httpData = [];

	private ?int $maxCopies = null;

	/** @var mixed[] */
	private ?array $purgedHttpData = null;

	/** @var mixed[] */
	private array $defaults = [];

	private int $minCopies;

	private bool $reached = false;

	/**
	 * @param mixed[] $httpData
	 * @param mixed[] $defaults
	 */
	public function __construct(array $httpData, array $defaults, ?int $maxCopies, int $minCopies)
	{
		$this->httpData = $httpData;
		$this->maxCopies = $maxCopies;
		$this->defaults = $defaults;
		$this->minCopies = $minCopies;

		foreach ($httpData as $index => $_) {
			if (str_starts_with((string) $index, Multiplier::SUBMIT_CREATE_NAME)) {
				$this->createAction = true;
				$num = substr($index, 18);
				if ($num) {
					$this->createNum = (int) $num;
				}

				return;
			}
		}

		foreach ($httpData as $index => $row) {
			if (is_array($row) && array_key_exists(Multiplier::SUBMIT_REMOVE_NAME, $row)) {
				$this->removeAction = true;
				$this->removeId = $index;

				break;
			}
		}
	}

	public function getCreateNum(): int
	{
		return $this->createNum;
	}

	/**
	 * @return mixed[]
	 */
	public function getDefaults(): array
	{
		return array_slice($this->defaults, 0, $this->maxCopies, true);
	}

	/**
	 * @return mixed[]
	 */
	public function getValues(): array
	{
		return array_slice($this->getPurgedHttpData(), 0, $this->maxCopies, true);
	}

	/**
	 * @return mixed[]
	 */
	public function getPurgedHttpData(): array
	{
		if ($this->purgedHttpData === null) {
			$httpData = $this->httpData;

			foreach ($httpData as $index => &$row) {
				if (!is_array($row)) {
					unset($httpData[$index]);
				} elseif (array_key_exists(Multiplier::SUBMIT_REMOVE_NAME, $row)) {
					unset($row[Multiplier::SUBMIT_REMOVE_NAME]);
				}
			}

			if ($this->isRemoveAction()) {
				if (count($httpData) > $this->minCopies) {
					unset($httpData[$this->getRemoveId()]);
				} else {
					$this->reached = true;
				}
			}

			$this->purgedHttpData = $httpData;
		}

		return $this->purgedHttpData;
	}

	public function isCreateAction(): bool
	{
		return $this->createAction;
	}

	public function isRemoveAction(): bool
	{
		return $this->removeAction;
	}

	public function getRemoveId(): int|string|null
	{
		return $this->removeId;
	}

	public function reachedMinLimit(): bool
	{
		return $this->reached;
	}

}
