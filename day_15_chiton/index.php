<?php

declare(strict_types=1);

$inputs = explode(PHP_EOL, trim(file_get_contents(__DIR__ . '/input.txt')));

// Part 1
$cavern = Cavern::create($inputs);
$lowest_cost = $cavern->getLowestTotalRisk();

echo 'What is the lowest total risk of any path from the top left to the bottom right?' . PHP_EOL;
echo $lowest_cost . PHP_EOL;


final class Cavern
{
    private int $width;
    private array $risk_levels;

    public function __construct(int $width, array $risk_levels)
    {
        $this->width = $width;
        $this->risk_levels = $risk_levels;
    }

    public static function create(array $inputs): self
    {
        return new self(strlen($inputs[0]), array_map('intval', array_merge(...array_map('str_split', $inputs))));
    }

    public function getLowestTotalRisk(): int
    {
        // What would Edsger Dijkstra do?
        // Implementation was inspired by https://github.com/fisharebest/algorithm/blob/main/src/Dijkstra.php
        $distances = array_fill(0, count($this->risk_levels), INF);
        $distances[0] = 0;

        $queue = [0 => 0];
        while(!empty($queue)) {
            // process next node in queue
            $closest_index = array_search(min($queue), $queue);
            if (!empty($this->risk_levels[$closest_index])) {
                foreach ($this->getAdjacent($closest_index) as $neighbor => $cost) {
                    if ($distances[$closest_index] + $cost < $distances[$neighbor]) {
                        // a shorter path was found
                        $distances[$neighbor] = $distances[$closest_index] + $cost;
                        $queue[$neighbor] = $distances[$neighbor];
                    } elseif ($distances[$closest_index] + $cost === $distances[$neighbor]) {
                        // an equally short path was found
                        $queue[$neighbor] = $distances[$neighbor];
                    }
                }
            }

            unset($queue[$closest_index]);
        }

        return end($distances);
    }

    /**
     * @param int $index
     * @return array<int, int>
     */
    private function getAdjacent(int $index): array
    {
        return array_filter([
           $index - $this->width => $this->risk_levels[$index - $this->width] ?? null,
           $index + 1 => $index % $this->width < $this->width - 1 && $index < count($this->risk_levels) - 1 ? $this->risk_levels[$index + 1] : null,
           $index + $this->width => $this->risk_levels[$index + $this->width] ?? null,
           $index - 1 => $index > 0 && $index % $this->width > 0 ? $this->risk_levels[$index - 1] : null,
        ], fn(?int $value): bool => $value !== null);
    }
}
