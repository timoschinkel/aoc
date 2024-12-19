<?php

declare(strict_types=1);

namespace Timoschinkel\Aoc2024\Day19;

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Stopwatch.php';

$input = __DIR__ . DIRECTORY_SEPARATOR . ($argv[1] ?? 'example') . '.txt';

$rows = array_filter(explode(PHP_EOL, file_get_contents($input)));

// Read input
$towels = explode(', ', $rows[0]);
$patterns = array_slice($rows, 1);

$sw = new \Stopwatch();

/**
 * Effectively I implemented a DFS with a number of optimizations; if a towel fits the pattern, we use it and seek if
 * we can find towels with the remainder of the pattern. This will work fine for the example input, but - as expected -
 * does not work with the input. That is achieved via three optimizations:
 * - Caching; the number of towel configurations is limited, once we have found if a pattern we don't need to check it
 *   again. This becomes more powerful the longer the pattern, as every combination of towels is now only checked once.
 *   This is a necessary optimization to solve this in somewhat reasonable time.
 * - Indexing the towels; by indexing the towels by the first character we  reduce the number of towels to check per
 *   pattern.
 * - Prioritizing towels with a longer pattern; this will find a valid pattern faster.
 *
 * @param array<string> $towels
 * @param array<string> $patterns
 * @return int
 */
function part_one(array $towels, array $patterns): int {
    // sort the towels by length
    usort($towels, fn(string $one, string $another): int => strlen($another) - strlen($one));

    $towel_index = [];
    foreach ($towels as $towel) {
        if (!isset($towel_index[$towel[0]])) {
            $towel_index[$towel[0]] = [];
        }

        $towel_index[$towel[0]][] = $towel;
    }

    $possible = 0;
    foreach ($patterns as $pattern) {
        if (is_possible_pattern($pattern, $towel_index)) {
            $possible++;
        }
    }

    return $possible;
}

/** @var array<string, boolean> $cache */
$cache = [];

function is_possible_pattern(string $pattern, array $available_towels, array $towels_in_pattern = []): bool {
    if ($pattern === '') {
        return true;
    }

    /**
     * This is an ugly PHP feature, but this is AoC, so I'll allow it.
     * @var array<string, boolean> $cache
     */
    global $cache;

    if (isset($cache[$pattern])) {
        return $cache[$pattern];
    }

    $towels = $available_towels[$pattern[0]] ?? [];
    if (count($towels) === 0) {
        $cache[$pattern] = false;
        return false;
    }

    foreach ($towels as $towel) {
        if (str_starts_with($pattern, $towel)) {
            if (is_possible_pattern(substr($pattern, strlen($towel)), $available_towels, array_merge($towels_in_pattern, [$towel]))) {
                $cache[$pattern] = true;
                return true;
            }
        }
    }

    $cache[$pattern] = false;
    return false;
}

$sw->start();
echo 'How many designs are possible? ' . part_one($towels, $patterns) . ' (' . $sw->ellapsed() . ')' . PHP_EOL;

/**
 * Same as part 1, but instead of caching `true` or `false` per pattern we cache the number of towel combinations for a
 * given pattern.
 *
 * @param array<string> $towels
 * @param array<string> $patterns
 * @return int
 */
function part_two(array $towels, array $patterns): int {
    // sort the towels by length
    usort($towels, fn(string $one, string $another): int => strlen($another) - strlen($one));

    $towel_index = [];
    foreach ($towels as $towel) {
        if (!isset($towel_index[$towel[0]])) {
            $towel_index[$towel[0]] = [];
        }

        $towel_index[$towel[0]][] = $towel;
    }

    $variations = 0;
    foreach ($patterns as $pattern) {
        $variations += count_patterns($pattern, $towel_index);
    }

    return $variations;
}

/** @var array<string, int> $count_cache */
$count_cache = [];

function count_patterns(string $pattern, array $available_towels): int {
    if ($pattern === '') {
        return 1;
    }

    global $count_cache;
    if (isset($count_cache[$pattern])) {
        return $count_cache[$pattern];
    }

    $towels = $available_towels[$pattern[0]] ?? [];
    if (count($towels) === 0) {
        $count_cache[$pattern] = 0;
        return 0;
    }

    $count = 0;
    foreach ($towels as $towel) {
        if (str_starts_with($pattern, $towel)) {
            $count += count_patterns(substr($pattern, strlen($towel)), $available_towels);
        }
    }

    $count_cache[$pattern] = $count;
    return $count;
}

$sw->start();
echo 'What do you get if you add up the number of different ways you could make each design? ' . part_two($towels, $patterns) . ' (' . $sw->ellapsed() . ')' . PHP_EOL;
