import { readFileSync } from 'fs';

const input = readFileSync(`${__dirname}/${process.argv[2] || 'example'}.txt`, { encoding:'utf8', flag:'r' }).trim().split("\n");
const debug: boolean = !!(process.env.DEBUG || false);

const games = input.map((line) => parse(line));

console.log('What is the sum of the IDs of those games?', part_one(games));
console.log('What is the sum of the power of these sets?', part_two(games));

function part_one(games: Game[]): number {
    /*
     * Proper input parsing! Once you parsed the input correctly it is a matter of iterating
     * over all the games and see if one of the "grabs" is exceeding the limits.
     */

    let sum = 0;

    for(const game of games) {
        const possible = game.subsets.filter((subset): boolean => {
            return subset.red <= 12 && subset.green <= 13 && subset.blue <= 14;
        })

        if (possible.length === game.subsets.length) {
            log(`Game ${game.id} is possible`);
            sum += game.id;
        } else {
            log(`Game ${game.id} is impossible`);
        }
    }

    return sum;
}

function part_two(games: Game[]): number {
    /*
     * Proper parsing in part 1 is paying off. The minimum amount of balls needed is
     * the maximum number of balls per color, per subset.
     */
    let sum = 0;

    for (const game of games) {
        const minimum = game.subsets.reduce(
            (carry, subset) => ({
                red: Math.max(carry.red, subset.red),
                blue: Math.max(carry.blue, subset.blue),
                green: Math.max(carry.green, subset.green),
            }),
            { red: 0, blue: 0, green: 0 }
        );

        sum += minimum.red * minimum.blue * minimum.green;
        log(`Game ${game.id}: ${minimum.red * minimum.blue * minimum.green}`);
    }

    return sum;
}

type Subset = {
    readonly red: number;
    readonly blue: number;
    readonly green: number;
}

type Game = {
    readonly id: number;
    readonly subsets: Subset[];
}

function parse(line: string): Game {
    const [header, body] = line.split(':');
    return {
        id: parseInt(header.trim().replace(/^Game (\d+)+$/si, '$1')),
        subsets: body.trim().split(';').map((subset) => {
            const s = { red: 0, green: 0, blue: 0 };
            subset.trim().split(',').forEach((grab) => {
                const [num, color] = grab.trim().split(' ');
                s[color] = parseInt(num);
            });
            return s;
        })
    };
}

function log(...args: unknown[]): void {
    if (debug) {
        console.log(...args);
    }
}
