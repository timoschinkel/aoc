<?php

declare(strict_types=1);

$inputs = explode(PHP_EOL, trim(file_get_contents(__DIR__ . '/input.txt')));

$growth_rate = 7;
$num_of_days = 80;

$population = array_map(fn(string $timer): LanternFish => new LanternFish($growth_rate, (int)$timer), explode(',', $inputs[0]));

for ($day = 0; $day < $num_of_days; $day++) {
    $newborns = [];
    foreach ($population as $fish) {
        $fish->tick();
        if ($fish->spawnsNewFish()) {
            $newborns[] = new LanternFish($growth_rate, $growth_rate + 1);
        }
    }
    $population = array_merge($population, $newborns);
}

echo 'How many lanternfish would there be after 80 days?' . PHP_EOL;
echo count($population) . PHP_EOL;

final class LanternFish
{
    private int $growth_rate;
    private int $timer;
    private bool $spawn = false;

    public function __construct(int $growth_rate, int $timer)
    {
        $this->growth_rate = $growth_rate;
        $this->timer = $timer;
    }

    public function tick(): void
    {
        if ($this->timer === 0) {
            $this->timer = $this->growth_rate - 1;
            $this->spawn = true;
        } else {
            $this->timer -= 1;
            $this->spawn = false;
        }
    }

    public function spawnsNewFish(): bool
    {
        return $this->spawn;
    }
}
