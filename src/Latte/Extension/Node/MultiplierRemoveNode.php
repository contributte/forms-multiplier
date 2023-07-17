<?php declare(strict_types = 1);

namespace Contributte\FormMultiplier\Latte\Extension\Node;

use Contributte\FormMultiplier\Multiplier;
use Latte\Compiler\Nodes\Php\Expression\ArrayNode;
use Latte\Compiler\Nodes\StatementNode;
use Latte\Compiler\PrintContext;
use Latte\Compiler\Tag;
use LogicException;
use Nette\ComponentModel\IComponent;
use Nette\Forms\Container;

final class MultiplierRemoveNode extends StatementNode
{

	public ArrayNode $attributes;

	public static function create(Tag $tag): self
	{
		$node = new self();
		$node->attributes = $tag->parser->parseArguments();

		return $node;
	}

	/**
	 * @param Container[] $formsStack
	 */
	public static function getRemoveButton(array $formsStack): ?IComponent
	{
		$container = end($formsStack);

		if (!$container || !$container->getParent() instanceof Multiplier) {
			throw new LogicException('{multiplier:remove} macro must be inside {multiplier} macro.');
		}

		return $container->getComponent(Multiplier::SUBMIT_REMOVE_NAME, false);
	}

	public function print(PrintContext $context): string
	{
		return $context->format(
			'if ($ʟ_input = %raw::getRemoveButton($this->global->formsStack)) {'
			. 'echo $ʟ_input->getControl()'
			. ($this->attributes->items ? '->addAttributes(%1.node)' : '')
			. ';'
			. '} %2.line',
			self::class,
			$this->attributes,
			$this->position
		);
	}

	public function &getIterator(): \Generator
	{
		yield $this->attributes;
	}

}
