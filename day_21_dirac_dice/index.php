<?php

declare(strict_types=1);

$inputs = explode(PHP_EOL, trim(file_get_contents(__DIR__ . '/input.txt')));

$players = [];
foreach ($inputs as $input) {
    $players[] = [
        'score' => 0,
        'start_position' => (int)substr($input, 28),
        'position' => (int)substr($input, 28),
    ];
}

// Part 1
$game = new Game();
$score = $game->getScoreWithDeterministicDie($players);

echo 'What do you get if you multiply the score of the losing player by the number of times the die was rolled during the game?' . PHP_EOL;
echo $score . PHP_EOL;

final class Game
{
    public function getScoreWithDeterministicDie(array $players): int
    {
        $dice = new DeterministicDice();

        $max_score = 0;
        while ($max_score < 1000) {
            foreach ($players as $i => $player) {
                // move player
                //echo 'Player ' . ($i + 1) . ' roles ' . ($one = $dice->roll()) . ' + ' . ($two = $dice->roll()) . ' + ' . ($three = $dice->roll());
                $players[$i]['position'] = ($player['position'] + $dice->roll() + $dice->roll() + $dice->roll() - 1) % 10 + 1;
                //echo ' and moves to space ' . $players[$i]['position'];
                $players[$i]['score'] += $players[$i]['position'];
                //echo ' for a total score of ' . $players[$i]['score'] . PHP_EOL;

                if ($players[$i]['score'] >= 1000) {
                    break 2;
                }
            }
            $max_score = max($max_score, $players[0]['score'], $players[1]['score']);
        }

        return ($players[0]['score'] >= 1000 ? $players[1]['score'] : $players[0]['score']) * $dice->getRolls();
    }
}

final class DeterministicDice
{
    private int $eyes = 1;
    private int $rolls = 0;

    public function roll(): int
    {
        $this->rolls++;
        return $this->eyes++;
    }

    public function getRolls(): int
    {
        return $this->rolls;
    }
}
