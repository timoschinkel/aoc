<?php

declare(strict_types=1);

$inputs = explode(PHP_EOL, trim(file_get_contents(__DIR__ . '/input.txt')));

// Part 1
$formula = Polymer::create($inputs);
$score = $formula->getScoreAfterSteps(10);

echo 'What do you get if you take the quantity of the most common element and subtract the quantity of the least common element?' . PHP_EOL;
echo $score . PHP_EOL;

final class Rule
{
    private string $pattern;
    private string $insertion;

    public function __construct(string $pattern, string $insertion)
    {
        $this->pattern = $pattern;
        $this->insertion = $insertion;
    }

    public function getPattern(): string
    {
        return $this->pattern;
    }

    public function getInsertion(): string
    {
        return $this->insertion;
    }

    public static function create(string $rule): self
    {
        return new self(substr($rule, 0, 2), substr($rule, 6));
    }
}

final class Polymer
{
    private string $template;

    /** @var array<string, Rule> */
    private array $rules;

    public function __construct(string $template, Rule ...$rules)
    {
        $this->template = $template;
        $this->rules = array_combine(
            array_map(fn(Rule $rule): string => $rule->getPattern(), $rules),
            array_values($rules)
        );
    }

    public function getScoreAfterSteps(int $step): int
    {
        $polymer = $this->template;
        $element_count = array_count_values(str_split($polymer));

        for($i = 0; $i < $step; $i++) {
            $polymer = $this->step($polymer, $element_count);
//            printf('After step %d: %s%s', $i + 1, $polymer, PHP_EOL);
        }

        asort($element_count);
        return end($element_count) - reset($element_count);
    }

    private function step(string $polymer, array &$element_count): string
    {
        $new = '';
        for ($i = 0; $i < strlen($polymer) - 1; $i++) {
            $new .= $polymer[$i];
            $chunk = substr($polymer, $i, 2);
            if ($this->rules[$chunk]) {
                $new .= $this->rules[$chunk]->getInsertion();
                $element_count[$this->rules[$chunk]->getInsertion()] = ($element_count[$this->rules[$chunk]->getInsertion()] ?? 0) + 1;
            }
        }

        return $new . $polymer[strlen($polymer) - 1];
    }

    public static function create(array $inputs): self
    {
        return new self(
            $inputs[0],
            ...array_map(
                fn(string $rule): Rule => Rule::create($rule),
                array_slice($inputs, 2)
            )
        );
    }
}
