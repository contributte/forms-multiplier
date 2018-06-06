<?php

namespace WebChemistry\Forms\Controls;

use Nette\Utils\Strings;

class ComponentResolver {

	/** @var string|int|null */
	private $removeId;

	/** @var bool */
	private $removeAction = false;

	/** @var bool */
	private $createAction = false;

	/** @var int */
	private $createNum = 1;

	/** @var array */
	private $httpData = [];

	/** @var int|null */
	private $maxCopies;

	/** @var array */
	private $purgedHttpData;

	/** @var array */
	private $values;

	/** @var int */
	private $minCopies;

	/** @var bool */
	private $reached = false;

	public function __construct(array $httpData, array $values, $maxCopies, $minCopies) {
		$this->httpData = $httpData;
		$this->maxCopies = $maxCopies;
		$this->values = $values;
		$this->minCopies = $minCopies;

		foreach ($httpData as $index => $_) {
			if (Strings::startsWith($index, Multiplier::SUBMIT_CREATE_NAME)) {
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

	/**
	 * @return int
	 */
	public function getCreateNum() {
		return $this->createNum;
	}

	public function getValuesForComponents() {
		return array_slice($this->getPurgedHttpData() ?: $this->values, 0, $this->maxCopies, true);
	}

	public function getPurgedHttpData() {
		if ($this->purgedHttpData === null) {
			$httpData = $this->httpData;

			foreach ($httpData as $index => &$row) {
				if (!is_array($row)) {
					unset($httpData[$index]);
				} else if (array_key_exists(Multiplier::SUBMIT_REMOVE_NAME, $row)) {
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

	public function isCreateAction() {
		return $this->createAction;
	}

	public function isRemoveAction() {
		return $this->removeAction;
	}

	public function getRemoveId() {
		return $this->removeId;
	}

	public function reachedMinLimit() {
		return $this->reached;
	}

}
