<?php

namespace WebChemistry\Forms\Controls;

class Helpers {

	public static function createButtonName(int $copyCount): string {
		return Multiplier::SUBMIT_CREATE_NAME . ($copyCount === 1 ? '' : $copyCount);
	}

}
