<?php

declare(strict_types=1);

$inputs = explode(PHP_EOL, trim(file_get_contents(__DIR__ . '/input.txt')));

// Step 1
$consortium = Consortium::create($inputs);
$flashes = $consortium->calculateNumberOfFlashes(100);

echo 'How many total flashes are there after 100 steps?' . PHP_EOL;
echo $flashes . PHP_EOL;

// Step 2
$consortium = Consortium::create($inputs);
$synchronized = $consortium->calculateFirstSynchronizedFlash();

echo 'What is the first step during which all octopuses flash?' . PHP_EOL;
echo $synchronized . PHP_EOL;

/**
 * Did you know a school of octopus is called a consortium?
 * @see https://www.theanimalfacts.com/glossary/animal-group-names/
 */
final class Consortium
{
    private int $width;

    /**
     * @see https://www.merriam-webster.com/words-at-play/the-many-plurals-of-octopus-octopi-octopuses-octopodes
     * @var array<int>
     */
    private array $octopuses;

    public function __construct(int $width, array $octopuses)
    {
        $this->width = $width;
        $this->octopuses = $octopuses;
    }

    public function calculateNumberOfFlashes(int $steps): int
    {
        $flashes = 0;
        for($step = 0; $step < $steps; $step++) {
//            echo 'After step' . $step . ': ' . $this . PHP_EOL;
            $flashes += $this->step();
        }

        return $flashes;
    }

    public function calculateFirstSynchronizedFlash(): int
    {
        $step = 0;
        do {
            $flashes = $this->step();
            $step++;
        } while ($flashes < count($this->octopuses));

        return $step;
    }

    private function step(): int
    {
        // Increase all energy levels with 1
        $this->octopuses = array_map(fn(int $energy): int => $energy + 1, $this->octopuses);

        // Detect flashes
        $to_flash = array_keys(array_filter($this->octopuses, fn(int $energy): bool => $energy > 9));
        if (count($to_flash) === 0) {
            // no flashes
            return 0;
        }

        $flashes = [];
        do {
            $flasher = array_shift($to_flash);
            $flashes[] = $flasher;

            $adjacent = $this->getAdjacentOctopuses($flasher);
            foreach (array_diff($adjacent, $flashes) as $prospect) {
                $this->octopuses[$prospect]++;

                if ($this->octopuses[$prospect] > 9) {
                    $to_flash[] = $prospect;
                }
            }
        } while (count($to_flash) > 0);

        $number_of_flashes = count(array_filter($this->octopuses, fn(int $energy): bool => $energy > 9));
        $this->octopuses = array_map(fn(int $energy): int => $energy > 9 ? 0 : $energy, $this->octopuses);

        return $number_of_flashes;
    }

    private function getAdjacentOctopuses(int $index): array
    {
        return array_values(array_filter([
            $this->getRelative($index, -1, -1),
            $this->getRelative($index, 0, -1),
            $this->getRelative($index, 1, -1),
            $this->getRelative($index, -1, 0),
            $this->getRelative($index, 1, 0),
            $this->getRelative($index, -1, 1),
            $this->getRelative($index, 0, 1),
            $this->getRelative($index, 1, 1),
        ], fn(?int $index): bool => $index !== null));
    }

    private function getRelative(int $index, int $dx, int $dy): ?int
    {
        $x = $index % $this->width;
        $y = floor($index / $this->width);

        if (($x + $dx) < 0 || ($x + $dx) > $this->width - 1) {
            // out of bounds
            return null;
        }

        if (($y + $dy) < 0 || ($y + $dy) >= floor(count($this->octopuses) / $this->width)) {
            // out of bounds
            return  null;
        }

        $new = (int)((($y + $dy) * $this->width) + ($x + $dx));

        return $new !== $index && $this->octopuses[$new] <= 9
            ? $new
            : null;
    }

    public static function create(array $input): self
    {
        return new self(strlen($input[0]), array_map('intval', array_merge(...array_map('str_split', $input))));
    }

    public function __toString(): string
    {
        return PHP_EOL . implode(
            PHP_EOL,
            array_map(
                fn(array $chunk): string => implode("", $chunk),
                array_chunk($this->octopuses, $this->width)
            )) . PHP_EOL;
    }
}
