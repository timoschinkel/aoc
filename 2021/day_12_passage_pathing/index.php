<?php

declare(strict_types=1);

$inputs = explode(PHP_EOL, trim(file_get_contents(__DIR__ . '/input.txt')));

$graph = Graph::create($inputs);

// Part 1
$num_of_paths = $graph->findNumberOfPaths('start', 'end');

echo 'How many paths through this cave system are there that visit small caves at most once?' . PHP_EOL;
echo $num_of_paths . PHP_EOL;

// Part 2
$num_of_paths_2 = $graph->findNumberOfPaths2('start', 'end');

echo 'How many paths through this cave system are there that visit small caves at most once?' . PHP_EOL;
echo $num_of_paths_2 . PHP_EOL;

final class Graph
{
    /** @var array<string, array<string>> */
    private array $edges;

    public function __construct(array $edges)
    {
        $this->edges = $edges;
    }

    public static function create(array $inputs): self
    {
        $edges = [];
        foreach ($inputs as $input) {
            [$start, $end] = explode('-', $input);

            // Bidirectional, so we can go from $start to $end, but also from $end to $start
            $edges[$start] = array_merge($edges[$start] ?? [], [$end]);
            $edges[$end] = array_merge($edges[$end] ?? [], [$start]);
        }

        return new self($edges);
    }

    public function findNumberOfPaths(string $start, string $end): int
    {
        $paths = $this->findPaths($start, $end, [$start]);
        return count($paths);
    }

    public function findNumberOfPaths2(string $start, string $end): int
    {
        $paths = $this->findPaths2($start, $end, [$start]);
        return count($paths);
    }

    /**
     * @param string $start
     * @param string $end
     * @param array<string> $path_so_far
     * @return array<array<string>>
     */
    private function findPaths(string $start, string $end, array $path_so_far = []): array
    {
        if ($start === $end) {
            return [$path_so_far];
        }

        // try all possible steps and add them to all paths
        $paths = [];
        foreach ($this->edges[$start] ?? [] as $node) {
            if ($this->isSmallCave($node) && in_array($node, $path_so_far)) {
                // we have already visited the small cave, making all $paths invalid
                continue;
            }

            $paths = array_merge($paths, $this->findPaths($node, $end, array_merge($path_so_far, [$node])));
        }

        return $paths;
    }

    /**
     * @param string $start
     * @param string $end
     * @param array<string> $path_so_far
     * @return array<array<string>>
     */
    private function findPaths2(string $start, string $end, array $path_so_far = []): array
    {
        if ($start === $end) {
            return [$path_so_far];
        }

        // try all possible steps and add them to all paths
        $paths = [];
        foreach ($this->edges[$start] ?? [] as $node) {
            if ($this->canVisitCave($path_so_far, $node) === false) {
                // we have already visited the small cave, making all $paths invalid
                continue;
            }

            $paths = array_merge($paths, $this->findPaths2($node, $end, array_merge($path_so_far, [$node])));
        }

        return $paths;
    }

    private function isSmallCave(string $cave): bool
    {
        return strtolower($cave) === $cave;
    }

    private function canVisitCave(array $path_so_far, string $cave): bool
    {
        if ($this->isSmallCave($cave) === false || !in_array($cave, $path_so_far)) {
            // if big cave or non-visited small cave, we can visit:
            return true;
        }

        if (in_array($cave, ['start', 'end']) && in_array($cave, $path_so_far)) {
            // exception: start and end can only be visited once
            return false;
        }

        $small_cave_visits = array_count_values(array_values(array_filter($path_so_far, fn(string $node): bool => $this->isSmallCave($node))));
        return count(array_filter($small_cave_visits, fn(int $visits): bool => $visits >= 2)) === 0;
    }
}
