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

final class MultiplierAddNode extends StatementNode
{

	public ExpressionNode $name;

	public ArrayNode $attributes;

	public ExpressionNode $part;

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

	public static function getCreateButton(Multiplier $multiplier, int|string $buttonId): ?Submitter
	{
		return $multiplier->getCreateButtons()[$buttonId] ?? null;
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

	public function &getIterator(): \Generator
	{
		yield $this->name;
		yield $this->attributes;
		yield $this->part;
	}

}
