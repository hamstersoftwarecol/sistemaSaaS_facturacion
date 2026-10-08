<?php

namespace Tests\Unit;

use App\Support\FormulaEvaluator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class FormulaEvaluatorTest extends TestCase
{
    public function test_it_evaluates_arithmetic_with_precedence(): void
    {
        $evaluator = new FormulaEvaluator;

        $this->assertSame(14.0, $evaluator->evaluate('2 + 3 * 4'));
        $this->assertSame(20.0, $evaluator->evaluate('(2 + 3) * 4'));
        $this->assertSame(-5.0, $evaluator->evaluate('-(2 + 3)'));
        $this->assertSame(512.0, $evaluator->evaluate('2 ^ 3 ^ 2'));
        $this->assertSame(1.0, $evaluator->evaluate('7 % 3'));
    }

    public function test_it_uses_variables_and_functions(): void
    {
        $evaluator = new FormulaEvaluator;
        $vars = ['subtotal' => 1200, 'tax' => 228];

        $this->assertSame(30.0, $evaluator->evaluate('subtotal * 0.025', $vars));
        $this->assertSame(60.0, $evaluator->evaluate('if(subtotal > 1000, subtotal * 0.05, 0)', $vars));
        $this->assertSame(0.0, $evaluator->evaluate('if(subtotal < 1000, 10, 0)', $vars));
        $this->assertSame(228.0, $evaluator->evaluate('max(tax, 100)', $vars));
        $this->assertSame(3.14, $evaluator->evaluate('round(3.14159, 2)'));
        $this->assertSame(0.0, $evaluator->evaluate('10 / 0'));
    }

    public function test_it_rejects_invalid_or_unsafe_expressions(): void
    {
        $evaluator = new FormulaEvaluator;

        $this->assertNotNull($evaluator->validate('system("ls")', ['subtotal']));
        $this->assertNotNull($evaluator->validate('subtotal *', ['subtotal']));
        $this->assertNotNull($evaluator->validate('unknown + 1', ['subtotal']));
        $this->assertNotNull($evaluator->validate('$subtotal', ['subtotal']));
        $this->assertNull($evaluator->validate('subtotal * 0.19', ['subtotal']));

        $this->expectException(InvalidArgumentException::class);
        $evaluator->evaluate('foo(1)');
    }
}
