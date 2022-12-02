<?php declare(strict_types = 1);

namespace Contributte\FormMultiplier\Latte\Extension\Node;

use Contributte\FormMultiplier\Multiplier;
use Contributte\FormMultiplier\Submitter;
use Latte\Compiler\Nodes\Php\Expression\ArrayNode;
use Latte\Compiler\Nodes\Php\ExpressionNode;
use Latte\Compiler\Nodes\Php\Scalar\StringNode;
use Latte\Compiler\Nodes\StatementNode;
use Latte\Compiler\PrintContext;
use Latte\Compiler\Tag;
use LogicException;
use Nette\ComponentModel\IComponent;
use Nette\Forms\Container;
use Nette\Forms\SubmitterControl;

final class MultiplierAddNode extends StatementNode
{

	/** @var ExpressionNode */
	public $name;

	/** @var ArrayNode */
	public $attributes;

	/** @var ExpressionNode */
	public $part;

	public static function create(Tag $tag): self
	{
		$tag->expectArguments('multiplier name');

		$node = new self();
		$node->name = $tag->parser->parseUnquotedStringOrExpression(false);

		if ($tag->parser->stream->tryConsume(':') && !$tag->parser->stream->is(',')) {
			$node->part = $tag->parser->isEnd()
				? new StringNode('1')
				: $tag->parser->parseUnquotedStringOrExpression();
		} else {
			$node->part = new StringNode('1');
		}

		$node->attributes = $tag->parser->parseArguments();

		return $node;
	}

	public function print(PrintContext $context): string
	{
		return $context->format(
			($this->name instanceof StringNode
				? '$ʟ_multiplier = end($this->global->formsStack)[%node];'
				: '$ʟ_multiplier = is_object($ʟ_tmp = %node) ? $ʟ_tmp : end($this->global->formsStack)[$ʟ_tmp];')
			. 'if ($ʟ_input = %raw::getCreateButton($ʟ_multiplier, %node)) {'
			. 'echo $ʟ_input->getControl()'
			. ($this->attributes->items ? '->addAttributes(%node)' : '')
			. ';'
			. '} %4.line',
			$this->name,
			self::class,
			$this->part,
			$this->attributes,
			$this->position
		);
	}

	/**
	 * @param int|string $buttonId
	 */
	public static function getCreateButton(Multiplier $multiplier, $buttonId): ?Submitter
	{
		return $multiplier->getCreateButtons()[$buttonId] ?? null;
	}

	public function &getIterator(): \Generator
	{
		yield $this->name;
		yield $this->attributes;
		yield $this->part;
	}
}
