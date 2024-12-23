<?php

declare(strict_types=1);

namespace Timoschinkel\Aoc2024\Day23;

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Stopwatch.php';

$input = __DIR__ . DIRECTORY_SEPARATOR . ($argv[1] ?? 'example') . '.txt';

$rows = array_filter(explode(PHP_EOL, file_get_contents($input)));

// Read input
$connections = [];
foreach ($rows as $row) {
    [$left, $right] = explode('-', $row);
    $connections[$left] = array_merge($connections[$left] ?? [], [$right]);
    $connections[$right] = array_merge($connections[$right] ?? [], [$left]);
}

$sw = new \Stopwatch();

function part_one(array $connections): int {
    $paths = [];
    $paths_with_t = 0;

    foreach ($connections as $first => $eligible_for_second) {
        $eligible_for_second = array_diff($eligible_for_second, [$first]);
        if (count($eligible_for_second) === 0) {
            continue; // no path
        }

        // $first: kh, $eligible_for_second: [tc, qp, ub, ta]
        foreach ($eligible_for_second as $candidate_for_second) {
            $eligible_for_third = array_diff($connections[$candidate_for_second] ?? [], [$first, $candidate_for_second]);
            if (count($eligible_for_third) === 0) {
                continue; // no path
            }

            foreach ($eligible_for_third as $candidate_for_third) {
                // Can the third actually get back to $first?
                if (in_array($first, $connections[$candidate_for_third] ?? [])) {
                    $path = [$first, $candidate_for_second, $candidate_for_third];

                    // Prevent duplicates
                    sort($path);
                    if (!isset($paths[join(',', $path)])) {
                        $paths[join(',', $path)] = $path;
                        $has_t = count(array_filter($path, fn(string $node): bool => $node[0] === 't')) > 0;
                        if ($has_t) $paths_with_t++;
                    }
                }
            }
        }
    }

//    print_r(array_keys($paths));
    return $paths_with_t;
}

$sw->start();
echo 'How many contain at least one computer with a name that starts with t? ' . part_one($connections) . ' (' . $sw->ellapsed() . ')' . PHP_EOL;

/**
 * This one reminded me of [day 25 of last year][2023-25]; once you know what graph algorithm you need to implement it
 * is actually not that difficult. In this case the algorithm needed is [Bron-Kerbosch][wikipedia]. The keyword for to
 * find this algorithm is "clique". I found a terrific video on [YouTube][youtube] that explained the algorithm really
 * well.
 *
 * [2023-25]: https://adventofcode.com/2023/day/25
 * [wikipedia]: https://en.wikipedia.org/wiki/Bron%E2%80%93Kerbosch_algorithm
 * [youtube]: https://www.youtube.com/watch?v=j_uQChgo72I
 *
 * @param array<string, array<string>> $connections
 * @return string
 */
function part_two(array $connections): string {
    $vertices = array_keys($connections);

    $maximum_cliques = [];
    bron_kerbosch([], $vertices, [], $maximum_cliques, $connections);

    $max = end($maximum_cliques);
    sort($max);
    return join(',', $max);
}

/**
 * This algorithm tries to make a clique by taking a vertex and try to find a suitable candidate in the intersection of
 * available vertices and the [neighborhood][neighborhood] of said vertex. Once we cannot add anymore vertices to the
 * clique, then we have found the maximum clique for that vertex.
 *
 * [neighborhood]: https://en.wikipedia.org/wiki/Neighbourhood_(graph_theory)
 *
 * @param array $current_clique
 * @param array $candidate_set
 * @param array $exclusion_clique
 * @param array $maximum_cliques
 * @param array $connections
 * @return void
 */
function bron_kerbosch(array $current_clique, array $candidate_set, array $exclusion_clique, array &$maximum_cliques, array $connections): void
{
    //echo 'bron_kerbosch, R: ' . join(', ', $current_clique) . ', P: ' . join(', ', $candidate_set) . ', X: ' . join(', ', $exclusion_clique) . PHP_EOL;
    if (count($candidate_set) === 0 && count($exclusion_clique) === 0) {
        //echo 'Found a maximum clique: ' . join(',', $current_clique) . PHP_EOL;
        if (count($maximum_cliques) === 0 || count($current_clique) > count(end($maximum_cliques))) {
            $maximum_cliques[] = $current_clique;
        }
    }

    foreach ($candidate_set as $vertex) {
        bron_kerbosch(
            array_merge($current_clique, [$vertex]),
            array_intersect($candidate_set, neighborhood($vertex, $connections)),
            array_intersect($exclusion_clique, neighborhood($vertex, $connections)),
            $maximum_cliques,
            $connections,
        );
        $candidate_set = array_filter($candidate_set, fn(string $v) => $v !== $vertex);
        $exclusion_clique[] = $vertex;
    }
}

function neighborhood(string $vertex, array $connections): array
{
    return $connections[$vertex] ?? [];
}

$sw->start();
echo 'What is the password to get into the LAN party? ' . part_two($connections) . ' (' . $sw->ellapsed() . ')' . PHP_EOL;
