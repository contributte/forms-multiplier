<?php declare(strict_types = 1);

namespace Contributte\FormMultiplier;

use Nette\Utils\Strings;

final class ComponentResolver
{

	/** @var string|int|null */
	private $removeId;

	/** @var bool */
	private $removeAction = false;

	/** @var bool */
	private $createAction = false;

	/** @var int */
	private $createNum = 1;

	/** @var mixed[] */
	private $httpData = [];

	/** @var int|null */
	private $maxCopies;

	/** @var mixed[] */
	private $purgedHttpData;

	/** @var mixed[] */
	private $values;

	/** @var int */
	private $minCopies;

	/** @var bool */
	private $reached = false;

	/**
	 * @param mixed[] $httpData
	 * @param mixed[] $values
	 */
	public function __construct(array $httpData, array $values, ?int $maxCopies, int $minCopies)
	{
		$this->httpData = $httpData;
		$this->maxCopies = $maxCopies;
		$this->values = $values;
		$this->minCopies = $minCopies;

		foreach ($httpData as $index => $_) {
			if (Strings::startsWith((string) $index, Multiplier::SUBMIT_CREATE_NAME)) {
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
	public function getValuesForComponents(bool $includeDefaults = true): array
	{
		return array_slice($this->getPurgedHttpData() ?: ($includeDefaults ? $this->values : []), 0, $this->maxCopies, true);
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

	/**
	 * @return int|string|null
	 */
	public function getRemoveId()
	{
		return $this->removeId;
	}

	public function reachedMinLimit(): bool
	{
		return $this->reached;
	}

}
