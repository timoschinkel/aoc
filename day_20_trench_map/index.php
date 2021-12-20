<?php

declare(strict_types=1);

$inputs = explode(PHP_EOL, trim(file_get_contents(__DIR__ . '/input.txt')));

/*
 * I needed some help with the "being careful to account for the infinite size of the images". I found what I did wrong
 * via https://topaz.github.io/paste/#XQAAAQAtBwAAAAAAAAAeD8qHAhQB6IibQv9eInnSb8LJ2hoAhp6HPuUdMiQw4+n8yKhBQ/CLHzk9xpu75qReaUEsA/dp3gX42Ga4nJRTjF7rgH9iQBObYkZPZcNJFm9bY/h4MYTvSP8jEI7oIss04bNzLfGWFVOTRu1prPrZBh1voZHn7FgwGEr/HPrjZ7AYAHMBx65ItMkwVynCLZ748Hf0gzVH8G1bvyxxk6hO0bPnku/d83QMKcVVZ6qO/cRvjaqRQUitIpUk1Ur7nLGvU4cAyZ4ATQrJ3u+DWpmy3LzTN8noj0EFfJuRx8Cq2S7zFQYnL1e4vuiHwII1QSTkt/fzGGXt/vrDX6Jp6Ddqhi/qF7V/Oc+9zDfGYIjZNp9aUylzq82AkrtF3Qy67o5N1/M00n2qAHXCSULwn+8IwNVAWOGUL2sDhJOuYZYDdXkG2YSetBHLotIxJrHDF2hBbSgOQKm0YDvkoWSjIDiPj1ZWfZy61sEOkj979i3PYO/Yz+DfN3g2drSJxoSAiFH6Pr96AFTtDJHCZ4txHDmzjWsCEjZubeZx/UuorOqMecWHpAdFGyynml8yamsm3t4nzDZ4xFtizayoeOvFjKypDYrCGLomqXKCt1u7Umbm+4EkQndOh0XRwtBVmK0F4PCWf0qxwAIeuXF351dvJITTMODwaQ0i1WIcwgEknQy6gFvra+x7aQ2gISme2l9UFBc6m/YUDm9JT1QKFgWSLnYzDCXUHiaFTgVjFKIvCVRr/c8BI+UHNCNTymxTOg6bQpfG9VxUazff7K5SRP0BMAOQkAriaDk6CLQsqOSG8QuNpyloDIU2o1eEjyvdDu1aEIqSDqR0TkcEKeGfNuY1+BVzM2Tx5qDNDVt34NKuRKpqwEMZOHzGyrpQyYaWVZy3RARknBKieu4f3ohRvGW+m/v501eVeg95FuGIrQH++3H/IA==
 */

// Part 1
$image = Image::create($inputs);
$num_of_pixels_lit = $image->getNumberOfPixelsLit(2);

echo 'How many pixels are lit in the resulting image?' . PHP_EOL;
echo $num_of_pixels_lit . PHP_EOL;

final class Image
{
    private string $algorithm;
    private int $width;
    private int $height;
    private string $pixels;

    public function __construct(string $algorithm, int $width, int $height, string $pixels)
    {
        $this->algorithm = $algorithm;
        $this->width = $width;
        $this->height = $height;
        $this->pixels = $pixels;
    }

    public static function create(array $inputs): self
    {
        $width = strlen($inputs[2]);
        $height = count($inputs) - 2;

        return new self(
            $inputs[0],
            $width,
            $height,
            join('', array_slice($inputs, 2))
        );
    }

    public function getNumberOfPixelsLit(int $iterations): int
    {
        // Every step add a border of 3 populated with non-lit pixels
        $edge = '.';

        for ($i = 0; $i < $iterations; $i++) {
            $this->expand(3, $edge ?? '.'); // expand with 3 with the value of $edge.
            $this->enhance();
            $edge = $this->pixels[$this->width + 1]; // Use the pixel at 1x1 to determine if the surrounding needs to be expanded with lit or non-lit pixels
            $this->collapse(2);
        }

        return substr_count($this->pixels, '#');
    }

    /**
     * Add border around the existing image to cater for "infinity"
     *
     * @param int $padding
     * @param string $char
     * @return void
     */
    public function expand(int $padding, string $char = '.'): void
    {
        $this->pixels = str_repeat($char, ($this->width + $padding + $padding) * $padding) . join('', array_map(
                fn(string $row): string => str_repeat($char, $padding) . $row . str_repeat($char, $padding),
                str_split($this->pixels, $this->width)
            )) . str_repeat($char, ($this->width + $padding + $padding) * $padding);

        $this->width += $padding + $padding;
        $this->height += $padding + $padding;
    }

    /**
     * Remove the border that we added with `expand()`
     *
     * @param int $padding
     * @return void
     */
    public function collapse(int $padding): void
    {
        $rows = str_split($this->pixels, $this->width);

        $this->pixels = join(
            '',
            array_map(
                fn(string $row): string => substr($row, $padding, strlen($row) - (2 * $padding)),
                array_slice($rows, $padding, $this->height - $padding - $padding)
            )
        );
        $this->width -= $padding + $padding;
        $this->height -= $padding + $padding;
    }

    public function enhance(): void
    {
        $enhanced = str_repeat('.', $this->width * $this->height);
        for ($row = 0; $row < $this->height; $row++) {
            for ($col = 0; $col < $this->width; $col++) {
                $index = $row * $this->width + $col;
                $binary = join('', [
                    $this->getRelative($index, -1, -1) === '#' ? 1 : 0,
                    $this->getRelative($index, 0, -1) === '#' ? 1 : 0,
                    $this->getRelative($index, 1, -1) === '#' ? 1 : 0,
                    $this->getRelative($index, -1, 0) === '#' ? 1 : 0,
                    $this->pixels[$index] === '#' ? 1 : 0,
                    $this->getRelative($index, 1, 0) === '#' ? 1 : 0,
                    $this->getRelative($index, -1, 1) === '#' ? 1 : 0,
                    $this->getRelative($index, 0, 1) === '#' ? 1 : 0,
                    $this->getRelative($index, 1, 1) === '#' ? 1 : 0,
                ]);

                $enhanced[$index] = $this->algorithm[bindec($binary)];
            }
        }

        $this->pixels = $enhanced;
    }

    private function getRelative(int $index, int $dx, int $dy): string
    {
        $x = $index % $this->width;
        $y = floor($index / $this->width);

        if (($x + $dx) < 0 || ($x + $dx) > $this->width - 1) {
            // out of bounds
            return '.';
        }

        if (($y + $dy) < 0 || ($y + $dy) >= floor(strlen($this->pixels) / $this->width)) {
            // out of bounds
            return '.';
        }

        $new = (int)((($y + $dy) * $this->width) + ($x + $dx));

        return $new !== $index
            ? $this->pixels[$new]
            : '.';
    }

    public function __toString(): string
    {
        return join(PHP_EOL, str_split($this->pixels, $this->width));
    }
}
