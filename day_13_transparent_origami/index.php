<?php

declare(strict_types=1);

$inputs = explode(PHP_EOL, trim(file_get_contents(__DIR__ . '/input.txt')));

// Part 1
$page = Page::create($inputs);
$visible_dots = $page->fold1();

echo 'How many dots are visible after completing just the first fold instruction on your transparent paper?' . PHP_EOL;
echo $visible_dots . PHP_EOL;

final class Page
{
    private array $positions;
    private array $folds;

    public function __construct(array $positions, array $folds)
    {
        $this->positions = $positions;
        $this->folds = $folds;
    }

    public function fold1(): int
    {
        $fold = array_shift($this->folds);

        ['direction' => $direction, 'position' => $line] = $fold;
        $positions = [];
        foreach ($this->positions as $position) {
            $new = [
                'x' => $direction === 'x' && $position['x'] >= $line ? ($line - ($position['x'] - $line)) : $position['x'],
                'y' => $direction === 'y' && $position['y'] >= $line ? ($line - ($position['y'] - $line)) : $position['y'],
            ];
            $positions[$new['x'] . '_' . $new['y']] = $new;
        }

        $this->positions = array_values($positions);

        return count($this->positions);
    }

    public static function create(array $inputs): self
    {
        $positions = [];
        $folds = [];

        foreach ($inputs as $input) {
            if ($input === '') continue;

            if (preg_match('%^(?P<x>\d+),(?P<y>\d+)$%si', $input, $matches) === 1) {
                $positions[] = ['x' => (int)$matches['x'], 'y' => (int)$matches['y']];
            } elseif (preg_match('%fold along (?P<direction>[xy])=(?P<position>\d+)%si', $input, $matches) === 1) {
                $folds[] = ['direction' => $matches['direction'], 'position' => (int)$matches['position']];
            }
        }

        return new self($positions, $folds);
    }

    public function __toString(): string
    {
        ['width' => $width, 'height' => $height] = array_reduce(
            $this->positions,
            fn(array $carry, array $item): array => ['width' => max($carry['width'], $item['x'] + 1), 'height' => max($carry['height'], $item['y'] + 1)],
            ['width' => 0, 'height' => 0]
        );

        $paper = array_fill(0, $width * $height, '.');
        foreach ($this->positions as ['x' => $x, 'y' => $y]) {
            $paper[$y * $width + $x] = '#';
        }

        return PHP_EOL .
            join(PHP_EOL, array_map('join', array_chunk($paper, $width))) .
            PHP_EOL;
    }
}
