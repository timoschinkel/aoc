<?php

declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Stopwatch.php';

$input = __DIR__ . DIRECTORY_SEPARATOR . ($argv[1] ?? 'example') . '.txt';

$rows = array_filter(explode(PHP_EOL, file_get_contents($input)));

// Read input
/** @var Array<int, int[]> $rules_ltr */
$rules_ltr = [];

/** @var Array<Array<int>> $updates */
$updates = [];

foreach ($rows as $row) {
    if (str_contains($row, '|')) {
        [$left, $right] = array_map('intval', explode('|', $row));
        $rules_ltr[$left] = [...($rules_ltr[$left] ?? []), $right];
    } else {
        $updates[] = array_map('intval', explode(',', $row));
    }
}

$sw = new Stopwatch();

function part_one(array $updates, array $rules_ltr): int {
    $result = 0;
    foreach ($updates as $update) {
        if (is_valid($update, $rules_ltr)) {
            $result += $update[floor(count($update) / 2)];
        }
    }
    return $result;
}

function is_valid(array $update, array $rules_ltr): bool {
    for ($i = 0; $i < count($update); $i++) {
        $right = array_slice($update, $i + 1);

        if (count(array_intersect($right, $rules_ltr[$update[$i]] ?? [])) !== count($right)) {
            // invalid!
            return false;
        }
    }

    return true;
}

$sw->start();
echo 'What do you get if you add up the middle page number from those correctly-ordered updates? ' . part_one($updates, $rules_ltr) . ' (' . $sw->ellapsed() . ')' . PHP_EOL;

function part_two(array $updates, array $rules_ltr): int {
    // We could probably reuse the outcome of part 1, but this works just as well.
    $invalid_updates = array_filter($updates, fn(array $update): bool => !is_valid($update, $rules_ltr));

    $result = 0;
    foreach ($invalid_updates as $update) {
        $fixed = fix_update($update, $rules_ltr);
        $result += $fixed[floor(count($fixed) / 2)];
    }
    return $result;
}

function fix_update(array $update, array $rules_ltr): array {
    for ($i = 0; $i < count($update); $i++) {
        $left = array_slice($update, 0, $i);
        $right = array_slice($update, $i + 1);

        if (count(array_intersect($right, $rules_ltr[$update[$i]] ?? [])) !== count($right)) {
            // invalid!
            $violators = array_diff($right, $rules_ltr[$update[$i]] ?? []);
            $swapped = [...$left, ...$violators, $update[$i], ...array_diff($right, $violators)];

            return fix_update($swapped, $rules_ltr);
        }
    }

    return $update;
}

$sw->start();
echo 'What do you get if you add up the middle page numbers after correctly ordering just those updates? ' . part_two($updates, $rules_ltr) . ' (' . $sw->ellapsed() . ')' . PHP_EOL;
