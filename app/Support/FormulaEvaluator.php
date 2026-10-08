<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Evaluador seguro de fórmulas empresariales (sin eval()).
 *
 * Soporta: números, variables, + - * / % ^, paréntesis, comparaciones
 * (> < >= <= == !=) y funciones: min, max, round, abs, ceil, floor, if.
 * Ejemplos: "subtotal * 0.02", "if(subtotal > 1000, subtotal * 0.05, 0)".
 */
class FormulaEvaluator
{
    protected array $tokens = [];

    protected int $pos = 0;

    protected array $variables = [];

    public const FUNCTIONS = ['min', 'max', 'round', 'abs', 'ceil', 'floor', 'if'];

    public function evaluate(string $expression, array $variables = []): float
    {
        $this->variables = array_change_key_case($variables, CASE_LOWER);
        $this->tokens = $this->tokenize($expression);
        $this->pos = 0;

        if (empty($this->tokens)) {
            throw new InvalidArgumentException('La fórmula está vacía.');
        }

        $value = $this->parseComparison();

        if ($this->pos < count($this->tokens)) {
            throw new InvalidArgumentException('Símbolo inesperado: '.$this->tokens[$this->pos]['value']);
        }

        return round($value, 6);
    }

    /**
     * Valida la sintaxis y las variables usando valores de ejemplo.
     */
    public function validate(string $expression, array $allowedVariables): ?string
    {
        try {
            $this->evaluate($expression, array_fill_keys($allowedVariables, 1));

            return null;
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
    }

    protected function tokenize(string $expression): array
    {
        $tokens = [];
        $length = strlen($expression);
        $i = 0;

        while ($i < $length) {
            $char = $expression[$i];

            if (ctype_space($char)) {
                $i++;

                continue;
            }

            if (ctype_digit($char) || ($char === '.' && $i + 1 < $length && ctype_digit($expression[$i + 1]))) {
                $start = $i;
                while ($i < $length && (ctype_digit($expression[$i]) || $expression[$i] === '.')) {
                    $i++;
                }
                $tokens[] = ['type' => 'number', 'value' => (float) substr($expression, $start, $i - $start)];

                continue;
            }

            if (ctype_alpha($char) || $char === '_') {
                $start = $i;
                while ($i < $length && (ctype_alnum($expression[$i]) || $expression[$i] === '_')) {
                    $i++;
                }
                $tokens[] = ['type' => 'ident', 'value' => strtolower(substr($expression, $start, $i - $start))];

                continue;
            }

            $two = substr($expression, $i, 2);
            if (in_array($two, ['>=', '<=', '==', '!='], true)) {
                $tokens[] = ['type' => 'op', 'value' => $two];
                $i += 2;

                continue;
            }

            if (str_contains('+-*/%^()<>,', $char)) {
                $tokens[] = ['type' => 'op', 'value' => $char];
                $i++;

                continue;
            }

            throw new InvalidArgumentException("Carácter no permitido: {$char}");
        }

        return $tokens;
    }

    protected function peek(): ?string
    {
        return $this->tokens[$this->pos]['value'] ?? null;
    }

    protected function isOp(string ...$ops): bool
    {
        $token = $this->tokens[$this->pos] ?? null;

        return $token && $token['type'] === 'op' && in_array($token['value'], $ops, true);
    }

    protected function expect(string $op): void
    {
        if (! $this->isOp($op)) {
            throw new InvalidArgumentException("Se esperaba '{$op}'.");
        }
        $this->pos++;
    }

    protected function parseComparison(): float
    {
        $left = $this->parseAdditive();

        while ($this->isOp('>', '<', '>=', '<=', '==', '!=')) {
            $op = $this->tokens[$this->pos++]['value'];
            $right = $this->parseAdditive();
            $left = match ($op) {
                '>' => $left > $right,
                '<' => $left < $right,
                '>=' => $left >= $right,
                '<=' => $left <= $right,
                '==' => abs($left - $right) < 1e-9,
                '!=' => abs($left - $right) >= 1e-9,
            } ? 1.0 : 0.0;
        }

        return $left;
    }

    protected function parseAdditive(): float
    {
        $value = $this->parseTerm();

        while ($this->isOp('+', '-')) {
            $op = $this->tokens[$this->pos++]['value'];
            $right = $this->parseTerm();
            $value = $op === '+' ? $value + $right : $value - $right;
        }

        return $value;
    }

    protected function parseTerm(): float
    {
        $value = $this->parseFactor();

        while ($this->isOp('*', '/', '%')) {
            $op = $this->tokens[$this->pos++]['value'];
            $right = $this->parseFactor();

            if (($op === '/' || $op === '%') && abs($right) < 1e-12) {
                $value = 0.0; // División por cero: se evalúa como 0 para no romper documentos.

                continue;
            }

            $value = match ($op) {
                '*' => $value * $right,
                '/' => $value / $right,
                '%' => fmod($value, $right),
            };
        }

        return $value;
    }

    protected function parseFactor(): float
    {
        $base = $this->parseUnary();

        if ($this->isOp('^')) {
            $this->pos++;
            $exponent = $this->parseFactor();

            return (float) pow($base, $exponent);
        }

        return $base;
    }

    protected function parseUnary(): float
    {
        if ($this->isOp('-')) {
            $this->pos++;

            return -$this->parseUnary();
        }
        if ($this->isOp('+')) {
            $this->pos++;

            return $this->parseUnary();
        }

        return $this->parsePrimary();
    }

    protected function parsePrimary(): float
    {
        $token = $this->tokens[$this->pos] ?? null;

        if (! $token) {
            throw new InvalidArgumentException('La fórmula termina de forma inesperada.');
        }

        if ($token['type'] === 'number') {
            $this->pos++;

            return $token['value'];
        }

        if ($token['type'] === 'ident') {
            $this->pos++;
            $name = $token['value'];

            if ($this->isOp('(')) {
                return $this->callFunction($name);
            }

            if (! array_key_exists($name, $this->variables)) {
                throw new InvalidArgumentException("Variable desconocida: {$name}");
            }

            return (float) $this->variables[$name];
        }

        if ($this->isOp('(')) {
            $this->pos++;
            $value = $this->parseComparison();
            $this->expect(')');

            return $value;
        }

        throw new InvalidArgumentException('Símbolo inesperado: '.$token['value']);
    }

    protected function callFunction(string $name): float
    {
        if (! in_array($name, self::FUNCTIONS, true)) {
            throw new InvalidArgumentException("Función desconocida: {$name}");
        }

        $this->expect('(');
        $args = [];

        if (! $this->isOp(')')) {
            $args[] = $this->parseComparison();
            while ($this->isOp(',')) {
                $this->pos++;
                $args[] = $this->parseComparison();
            }
        }

        $this->expect(')');

        $count = count($args);
        $require = function (int $min, ?int $max = null) use ($name, $count) {
            if ($count < $min || ($max !== null && $count > $max)) {
                throw new InvalidArgumentException("Número de argumentos inválido para {$name}().");
            }
        };

        switch ($name) {
            case 'min':
                $require(1);

                return min($args);
            case 'max':
                $require(1);

                return max($args);
            case 'round':
                $require(1, 2);

                return round($args[0], (int) ($args[1] ?? 0));
            case 'abs':
                $require(1, 1);

                return abs($args[0]);
            case 'ceil':
                $require(1, 1);

                return ceil($args[0]);
            case 'floor':
                $require(1, 1);

                return floor($args[0]);
            case 'if':
                $require(3, 3);

                return $args[0] != 0 ? $args[1] : $args[2];
        }

        return 0.0;
    }
}
