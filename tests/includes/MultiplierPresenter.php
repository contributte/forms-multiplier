<?php

class MultiplierPresenter extends \Nette\Application\UI\Presenter {

	/** @var \Nette\Application\UI\Form */
	private $form;

	/**
	 * @return \Nette\Application\UI\Form
	 */
	public function getForm() {
		if (!$this->form) {
			$this->form = new \Nette\Application\UI\Form();
		}

		return $this->form;
	}

	protected function createComponentMultiplier() {
		return $this->getForm();
	}

}
