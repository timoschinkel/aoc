<?php

declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Stopwatch.php';

$input = __DIR__ . DIRECTORY_SEPARATOR . ($argv[1] ?? 'example') . '.txt';

$rows = array_filter(explode(PHP_EOL, file_get_contents($input)));

$one = [];
$two = [];
foreach ($rows as $row) {
    [$left, $right] = preg_split('%\s+%', $row, 2);
    $one[] = $left;
    $two[] = $right;
}

$sw = new Stopwatch();

function part_one(array $one, array $two): int {
    sort($one);
    sort($two);

    $difference = 0;
    for ($i = 0; $i < count($one); $i++) {
        $difference += abs($one[$i] - $two[$i]);
    }

    return $difference;
}

$sw->start();
echo 'What is the total distance between your lists? ' . part_one($one, $two) . ' (' . $sw->ellapsed() . ')' . PHP_EOL;

function part_two(array $one, array $two): int {
    $counts = array_count_values($two); // hail PHP!

    $score = 0;
    foreach ($one as $id) {
        $score += $id * ($counts[$id] ?? 0);
    }

    return $score;
}

$sw->start();
echo 'What is their similarity score? ' . part_two($one, $two) . ' (' . $sw->ellapsed() . ')' . PHP_EOL;
