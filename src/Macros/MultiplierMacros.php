<?php

namespace WebChemistry\Forms\Controls\Macros;

use Latte\CompileException;
use Latte\Compiler;
use Latte\MacroNode;
use Latte\Macros\MacroSet;
use Latte\PhpWriter;
use WebChemistry\Forms\Controls\Multiplier;

final class MultiplierMacros extends MacroSet {

	public static function install(Compiler $compiler) {
		$me = new static($compiler);

		$me->addMacro('multiplier', [$me, 'multiplierMacro'], 'array_pop($this->global->formsStack); $formContainer = $_form = end($this->global->formsStack); }');
		$me->addMacro('btnCreate', [$me, 'createMacro']);
		$me->addMacro('btnRemove', [$me, 'removeMacro']);
	}

	public function createMacro(MacroNode $node, PhpWriter $writer) {
		if ($node->modifiers) {
			throw new CompileException('Modifiers are not allowed in ' . $node->getNotation());
		}
		$words = $node->tokenizer->fetchWords();
		if (!$words) {
			throw new CompileException('Missing name in ' . $node->getNotation());
		}
		$node->replaced = true;
		$name = array_shift($words);
		if (isset($words[0]) && is_numeric($words[0])) {
			$copyCount = array_shift($words);
		} else {
			$copyCount = 1;
		}

		$code = $name[0] === '$' ? '$_multiplier = is_object(%0.word) ? %0.word : end($this->global->formsStack)[%0.word];' : '$_multiplier = end($this->global->formsStack)[%0.word];';
		$code .= 'if (isset($_multiplier->getCreateButtons()[%1.word])) {';
		$code .= '$_input = $_multiplier->getCreateButtons()[%1.word];';

		return $writer->write(
			$code
			. 'echo $_input'
			. '->%2.raw'
			. ($node->tokenizer->isNext() ? '->addAttributes(%node.array);' : ';')
			. '}',
			$name,
			$copyCount,
			$words ? 'getControlPart(' . implode(', ', array_map([$writer, 'formatWord'], $words)) . ')' : 'getControl()'
		);
	}

	public function removeMacro(MacroNode $node, PhpWriter $writer) {
		if ($node->modifiers) {
			throw new CompileException('Modifiers are not allowed in ' . $node->getNotation());
		}
		$code = '$_tmp = end($this->global->formsStack);';
		$code .= '$_input = isset($_tmp[\'' . Multiplier::SUBMIT_REMOVE_NAME . '\']) ? $_tmp[\'' . Multiplier::SUBMIT_REMOVE_NAME . '\'] : null;';
		$code .= 'if ($_input) {';
		$code .= 'echo $_input->getControl()';
		$node->replaced = true;

		return $writer->write(
			$code
			. ($node->tokenizer->isNext() ? '->addAttributes(%node.array);' : ';')
			. '}'
		);
	}

	public function multiplierMacro(MacroNode $node, PhpWriter $writer) {
		if ($node->modifiers) {
			throw new CompileException('Modifiers are not allowed in ' . $node->getNotation());
		}
		$words = $node->tokenizer->fetchWords();
		if (!$words) {
			throw new CompileException('Missing name in ' . $node->getNotation());
		}
		$node->replaced = true;
		$name = array_shift($words);

		return $writer->write(
			(
			$name[0] === '$' ?
				'$_multiplier = is_object(%0.word) ? %0.word : end($this->global->formsStack)[%0.word];' : // variable
				'$_multiplier = end($this->global->formsStack)[%0.word];' // string
			)
			. 'foreach ($_multiplier->getContainers() as $_multiplier) {' . "\n"
			. '$this->global->formsStack[] = $formContainer = $_multiplier',
			$name
		);
	}

}
