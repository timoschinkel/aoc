<?php

declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Stopwatch.php';

$input = __DIR__ . DIRECTORY_SEPARATOR . ($argv[1] ?? 'example') . '.txt';

$rows = array_filter(explode(PHP_EOL, file_get_contents($input)));

// Read input

$sw = new Stopwatch();

/**
 * Building up the filesystem before calculating the checksum will be way to memory intensive, and it is not necessary.
 * By maintaining the position we can iterate over the files/free combinations and calculate the checksum. When we
 * encounter a free block we populate it from the "right". The trick was to ensure your administration is correct and
 * you handle all edge cases.
 *
 * @param string $disk_map
 * @return int
 */
function part_one(string $disk_map): int {
    $chunks = [];
    for ($i = 0; $i < ceil(strlen($disk_map) / 2); $i++) {
        $chunks[] = [intval($disk_map[$i * 2]), intval($disk_map[$i * 2 + 1] ?? 0)];
    }

    $checksum = 0;
    $position = 0;

    $left_index = 0;
    $right_index = count($chunks) - 1;

    while ($left_index < $right_index) {
        $chunk = $chunks[$left_index];
        // File block
        for ($i = 0; $i < $chunk[0]; $i++) {
            $checksum += $position * $left_index;
            $position++;
        }

        // Free space
        for ($i = 0; $i < $chunk[1]; $i++) {
            // we need to grab from $chunks[$right_position]
            $last = $chunks[$right_index];
            while ($last[0] <= 0) {
                $last = $chunks[--$right_index];
            }

            if ($left_index >= $right_index) {
                // We might be in the last block by now, we don't need to handle the block anymore.
                break;
            }

            $checksum += $position * $right_index;
            $chunks[$right_index][0]--;
            $position++;
        }

        // Increase left index to go to the next iteration
        $left_index++;
    }

    // Maybe there's something left
    if ($left_index === $right_index && $chunks[$right_index][0] > 0) {
        for ($i = 0; $i < $chunks[$left_index][0]; $i++) {
            //$filesystem .= $left_index;
            $checksum += $position * $left_index;
            $position++;
        }
    }

    return $checksum;
}

$sw->start();
echo 'What is the resulting filesystem checksum? ' . part_one(reset($rows)) . ' (' . $sw->ellapsed() . ')' . PHP_EOL;

/**
 * The keyword is proper administration; we need to keep track of what block we used, so we don't reuse it. When that is
 * set up properly, then it is a matter of iterating over the blocks, when we find a free block we walk from the end of
 * the list forward until we find an unused block that fits. The trick here is that multiple blocks might fit in the
 * same free space.
 *
 * @param string $disk_map
 * @return int
 */
function part_two(string $disk_map): int {
    $chunks = [];
    for ($i = 0; $i < ceil(strlen($disk_map) / 2); $i++) {
        $chunks[] = [intval($disk_map[$i * 2]), intval($disk_map[$i * 2 + 1] ?? 0), false];
    }

    $checksum = 0;
    $position = 0;

    foreach (array_keys($chunks) as $left_index) {
        $chunk = $chunks[$left_index];

        // File block
        for ($i = 0; $i < $chunk[0]; $i++) {
            if (!$chunk[2]) {
                $checksum += $position * $left_index;
            }
            $position++;
        }

        if ($chunk[1] === 0) {
            continue;
        }

        // Free space
        // Starting from the end of the list find a block with the same size
        $space_left = $chunk[1];
        $right_index = find_block($chunks, $left_index, $space_left);
        while ($right_index !== -1) {
            // Populate
            for ($i = 0; $i < $chunks[$right_index][0]; $i++) {
                $checksum += $position * $right_index;
                $position++;
                $space_left--;
            }

            $chunks[$right_index][2] = true;

            $right_index = find_block($chunks, $left_index, $space_left);
        }

        // If we could not find a fit, we fill with empty
        $position += $space_left;
    }

    return $checksum;
}

function find_block(array $chunks, int $start, int $size): int {
    for ($i = count($chunks) - 1; $i > $start; $i--) {
        if (!$chunks[$i][2] && $chunks[$i][0] <= $size) {
            return $i;
        }
    }

    return -1; // not found
}

$sw->start();
echo 'What is the resulting filesystem checksum? ' . part_two(reset($rows)) . ' (' . $sw->ellapsed() . ')' . PHP_EOL;
