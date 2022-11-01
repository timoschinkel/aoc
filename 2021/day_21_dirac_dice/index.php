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

echo PHP_EOL;

// Part 2
$game = new Game();
$score = $game->getScoreWithDiracDie($players);

echo 'Find the player that wins in more universes; in how many universes does that player win?' . PHP_EOL;
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

    public function getScoreWithDiracDie(array $players): int
    {
        // Calculate the possible outcomes of throwing three three-sided dice
        $possible_throws = [];
        for($i = 1; $i <= 3; $i++) { for($j = 1; $j <= 3; $j++) { for($k = 1; $k <= 3; $k++) { $possible_throws[] = $i + $j + $k; }}}

        // Calculate the chances of hitting a certain number of eyes, eg. when throwing three three-sided dice there
        // is a 1 on 27 chance to throw 3 eyes, but a chance of 7 on 27 to throw 6 eyes.
        $dice_frequencies = array_count_values($possible_throws);

        // This is a variation of Day 6: Lanternfish
        //
        // List all possible states and the number of universes where this state is active. The state is determined by
        // the position and score of player one and the position and score of player two. I use `json_encode()` to make
        // a string representation of the game state. Got that trick from the solution code of Topaz.
        // We start out with a single state; The state where with the start positions of both players and a score of 0.
        // See also https://www.reddit.com/r/adventofcode/comments/rlairt/comment/hpeusa4, where I got this insight.
        //
        // While we have non-winning states we take these states and determine the possible outcomes. We do this by
        // calculating every possible outcome based on the $dice_frequencies. If the result is a winning state we add it
        // to the number of wins for the active player. If the result is not a winning state we add the outcome back to
        // the list of game states that need to be processed in the next iteration.

        // We have 1 universe with our start state:
        $state = [
            json_encode([$players[0]['start_position'], 0, $players[1]['start_position'], 0]) => 1,
        ];

        // Alternative between the active player
        $active_player = 0;

        // Administrate wins per player
        $player_one_wins = 0;
        $player_two_wins = 0;

        $iteration = 0; // keeping the iteration is purely for printing debug messages.

        // While we have game states to process keep processing them.
        while (count($state) > 0) {
            $iteration++;

            // Initiate a new state
            $updated_state = [];

            foreach ($state as $game_state => $number_of_universes) {
                // For evert game state, decode the positions and scores of players 1 and 2
                [$position_0, $score_0, $position_1, $score_1] = json_decode($game_state);

                foreach ($dice_frequencies as $eyes => $frequency) {
                    // Calculate the new position for $game_state based on result of the dice rolls - $eyes
                    if ($active_player === 0) {
                        $new_position = ($position_0 + $eyes - 1) % 10 + 1;
                        $new_state = [
                            $new_position,
                            $score_0 + $new_position,
                            $position_1, $score_1
                        ];
                    } else {
                        $new_position = ($position_1 + $eyes - 1) % 10 + 1;
                        $new_state = [
                            $position_0, $score_0,
                            $new_position,
                            $score_1 + $new_position
                        ];
                    }

                    // The $new_state occurs in $number_of_universes * $frequency. This needs to be applied to both
                    // adding to game states to be processed and win registration.

                    // Check win conditions
                    if ($active_player === 0 && $new_state[1] >= 21) {
                        //echo 'Win conditions for player 1: ' . json_encode($new_state) . ' x ' . ($number_of_universes * $frequency) . PHP_EOL;
                        $player_one_wins += $number_of_universes * $frequency;
                    } elseif ($active_player === 1 && $new_state[3] >= 21) {
                        //echo 'Win conditions for player 2: ' . json_encode($new_state) . ' x ' . ($number_of_universes * $frequency) . PHP_EOL;
                        $player_two_wins += $number_of_universes * $frequency;
                    } else {
                        // No win condition, so we have a new possible game state. Add it to $updated_states making sure
                        // to consider any prior states that lead to $new_state.
                        $updated_state[json_encode($new_state)] = ($updated_state[json_encode($new_state)] ?? 0) + $number_of_universes * $frequency;
                    }
                }
            }

            //echo 'Iteration ' . $iteration . ': '. count($updated_state) . ' states left, number of universes: ' . array_sum($updated_state) . ' / ' . (array_sum($updated_state) + $player_one_wins + $player_two_wins) . ', wins player 1:' . $player_one_wins . ', wins player 2 : ' . $player_two_wins . PHP_EOL;

            // Switch the active player between 1 and 0
            $active_player = ($active_player + 1) % 2;

            // Make $updated_state the list of states to be processed.
            $state = $updated_state;
        }

        return max($player_one_wins, $player_two_wins);
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
