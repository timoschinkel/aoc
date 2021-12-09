<?php

declare(strict_types=1);

$inputs = explode(PHP_EOL, trim(file_get_contents(__DIR__ . '/input.txt')));

$map = HeightMap::create($inputs);

// Part 1:
echo 'What is the sum of the risk levels of all low points on your heightmap?' . PHP_EOL;
echo $map->getLowPointsScore() . PHP_EOL;

// Part 2:
echo 'What do you get if you multiply together the sizes of the three largest basins?' . PHP_EOL;
echo $map->getLargestBasinScore() . PHP_EOL;

final class HeightMap
{
    private int $width;
    private array $measurements;

    /**
     * @param int $width
     * @param int[] $measurements
     */
    public function __construct(int $width, array $measurements)
    {
        $this->width = $width;
        $this->measurements = $measurements;
    }

    public static function create(array $inputs): self
    {
        return new self(
            strlen($inputs[0]),
            array_map('intval', array_merge(...array_map('str_split', $inputs)))
        );
    }

    public function getLowPointsScore(): int
    {
        $score = 0;
        foreach ($this->measurements as $index => $measurement) {
            if ($this->isLowPoint($index)) {
                $score += $measurement + 1;
            }
        }

        return $score;
    }

    public function getLargestBasinScore(): int
    {
        $basins = [];
        foreach ($this->measurements as $index => $measurement) {
            if ($this->isLowPoint($index)) {
                $basins[] = $this->getBasin($index);
            }
        }

        // order basins by length
        usort($basins, fn(array $one, array $another) => count($another) <=> count($one));

        return array_product(array_map('count', array_slice($basins, 0, 3)));
    }

    private function isLowPoint(int $index): bool
    {
        $measurement = $this->measurements[$index];

        return
            $measurement < ($this->measurements[$index-$this->width] ?? PHP_INT_MAX) && // up
            (($index + 1) % $this->width === 0 || $measurement < ($this->measurements[$index + 1] ?? PHP_INT_MAX)) && // right
            $measurement < ($this->measurements[$index+$this->width] ?? PHP_INT_MAX) && // down
            ($index % $this->width === 0 || $measurement < ($this->measurements[$index - 1] ?? PHP_INT_MAX)); // left
    }

    private function getBasin(int $low_point): array
    {
        $basin = [];

        $stack = [$low_point];
        while (count($stack) > 0) {
            $index = array_shift($stack);

            if ($this->measurements[$index] !== 9) {
                $basin[] = $index;

                // up
                if ($index > $this->width && !in_array($index - $this->width, $basin) && !in_array($index - $this->width, $stack)) {
                    $stack[] = $index - $this->width;
                }

                // right
                if (($index + 1) % $this->width !== 0 && !in_array($index + 1, $basin) && !in_array($index + 1, $stack)) {
                    $stack[] = $index + 1;
                }

                // down
                if ($index + $this->width < count($this->measurements) && !in_array($index + $this->width, $basin) && !in_array($index + $this->width, $stack)) {
                    $stack[] = $index + $this->width;
                }

                // left
                if ($index % $this->width !== 0 && !in_array($index - 1, $basin) && !in_array($index - 1, $stack)) {
                    $stack[] = $index - 1;
                }
            }
        }

//        echo PHP_EOL;
//        foreach ($this->measurements as $index => $measurement) {
//            echo in_array($index, $basin) ? $measurement : ' ';
//            if (($index + 1) % $this->width === 0) {
//                echo PHP_EOL;
//            }
//        }

        return $basin;
    }

    public function __toString(): string
    {
        $str = PHP_EOL;
        for ($y = 0; $y < count($this->measurements) / $this->width; $y++) {
            $str .= implode('', array_slice($this->measurements, $y * $this->width, $this->width)) . PHP_EOL;
        }
        $str .= PHP_EOL;

        for ($y = 0; $y < count($this->measurements) / $this->width; $y++) {
            for ($x = 0; $x < $this->width; $x++) {
                $str .= $this->isLowPoint($y * $this->width + $x) ? $this->measurements[$y * $this->width + $x] : ' ';
            }
            $str .= PHP_EOL;
        }

        return $str . PHP_EOL;
    }
}
