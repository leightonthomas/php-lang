<?php

declare(strict_types=1);

namespace App\Compiler\CustomBytecode;

use App\Model\Compiler\CustomBytecode\Opcode;
use App\Model\Syntax\Simple\BlockReturn;
use App\Model\Syntax\Simple\Definition\FunctionDefinition;
use App\Model\Syntax\Simple\Definition\VariableDefinition;
use App\Model\Syntax\Simple\Infix\Addition;
use App\Model\Syntax\Simple\Infix\FunctionCall;
use App\Model\Syntax\Simple\Infix\Subtraction;
use App\Model\Syntax\Simple\IntegerLiteral;
use App\Model\Syntax\Simple\Prefix\Group;
use App\Model\Syntax\Simple\Prefix\Minus;
use App\Model\Syntax\Simple\StringLiteral;
use App\Model\Syntax\Simple\Variable;
use App\Model\Syntax\SubExpression;
use RuntimeException;

use function bin2hex;
use function get_class;
use function intval;
use function join;
use function mb_strlen;
use function pack;

final class FunctionCompiler
{
    /** @var list<string> */
    private array $instructions = [];

    public function compile(FunctionDefinition $definition): string
    {
        // reset
        $this->instructions = [];

        $hadReturnStatement = false;
        foreach ($definition->codeBlock->expressions as $expression) {
            if ($expression instanceof BlockReturn) {
                $hadReturnStatement = true;
                $returnExpr = $expression->expression;
                if ($returnExpr !== null) {
                    $this->writeSubExpression($expression->expression);
                } else {
                    $this->instructions[] = pack("S", Opcode::PUSH_UNIT->value);
                }

                $this->instructions[] = pack("S", Opcode::RET->value);

                continue;
            }

            if ($expression instanceof VariableDefinition) {
                $varName = $expression->name->identifier;

                $this->writeSubExpression($expression->value);
                $this->instructions[] = pack("SQH*", Opcode::LET->value, mb_strlen($varName), bin2hex($varName));

                continue;
            }

            if ($expression instanceof SubExpression) {
                $this->writeSubExpression($expression);

                continue;
            }

            throw new RuntimeException("Unhandled expression: " . get_class($expression));
        }

        if (! $hadReturnStatement) {
            $this->instructions[] = pack("SS", Opcode::PUSH_UNIT->value, Opcode::RET->value);
        }

        return join('', $this->instructions);
    }

    private function writeSubExpression(SubExpression $expression): void
    {
        if ($expression instanceof IntegerLiteral) {
            $this->instructions[] = pack("SQ", Opcode::PUSH_INT->value, intval($expression->base->integer));

            return;
        }

        if ($expression instanceof StringLiteral) {
            $literal = $expression->base->content;

            $this->instructions[] = pack("SQH*", Opcode::PUSH_STRING->value,  mb_strlen($literal), bin2hex($literal));

            return;
        }

        if ($expression instanceof Variable) {
            $varName = $expression->base->identifier;

            $this->instructions[] = pack("SQH*", Opcode::LOAD->value, mb_strlen($varName), bin2hex($varName));

            return;
        }

        if ($expression instanceof Group) {
            $this->writeSubExpression($expression->operand);

            return;
        }

        if ($expression instanceof Subtraction) {
            $this->writeSubExpression($expression->left);
            $this->writeSubExpression($expression->right);

            $this->instructions[] = pack("S", Opcode::SUB->value);

            return;
        }

        if ($expression instanceof Addition) {
            $this->writeSubExpression($expression->left);
            $this->writeSubExpression($expression->right);

            $this->instructions[] = pack("S", Opcode::ADD->value);

            return;
        }

        if ($expression instanceof Minus) {
            $this->writeSubExpression($expression->operand);

            $this->instructions[] = pack("S", Opcode::NEG->value);

            return;
        }

        if ($expression instanceof FunctionCall) {
            $on = $expression->on;
            if (! ($on instanceof Variable)) {
                throw new RuntimeException('only calling on vars supported atm');
            }

            foreach ($expression->arguments as $arg) {
                $this->writeSubExpression($arg);
            }

            $varName = $on->base->identifier;

            $this->instructions[] = pack("SQH*", Opcode::CALL->value, mb_strlen($varName), bin2hex($varName));

            return;
        }

        throw new RuntimeException("Unhandled subexpression: " . get_class($expression));
    }
}
