import { readFileSync } from 'fs';
import { Stopwatch } from '../stopwatch';

const input = readFileSync(`${__dirname}/${process.argv[2] || 'example'}.txt`, { encoding:'utf8', flag:'r' }).trim().split("\n").map(line => line.trim());
const debug: boolean = !!(process.env.DEBUG || false);

const card_ranks = {
    '2': 2,
    '3': 3,
    '4': 4,
    '5': 5,
    '6': 6,
    '7': 7,
    '8': 8,
    '9': 9,
    'T': 10,
    'J': 11,
    'Q': 12,
    'K': 13,
    'A': 14,
}

class Game {
    constructor(
        public readonly cards: string[],
        public readonly bid: number,
    ) {
    }

    public compareTo(another: Game): number {
        const r1 = rank(this);
        const r2 = rank(another);

        if (r1 != r2) {
            return r1 - r2;
        }

        // same type of hand, compare the cards from the left to the right
        for (let i = 0; i < this.cards.length; i++) {
            if (this.cards[i] !== another.cards[i]) {
                return card_ranks[this.cards[i]] - card_ranks[another.cards[i]];
            }
        }

        // they're the same
        return 0;
    }
}

const games = parse(input);

{
    using sw = new Stopwatch('part one');
    console.log('What are the total winnings?', part_one(games));
}

function part_one(games: Game[]): number {
    /**
     * Note to self; read the assignment and do not assume the regular poker rules...
     *
     * Read the input and count the number of occurrences for each card. Depending on those
     * counts can we determine the type of hand. I give this a numeric value so I can easily
     * sort using this type. When the types of two hands are the same compare the cards from
     * left to right.
     */
    games.sort((one, another) => one.compareTo(another));

    let sum = 0;
    for(let i = 0; i < games.length; i++) {
        sum += (i + 1) * games[i].bid;
    }

    return sum;
}

function parse(input: string[]): Game[] {
    return input.map((line) => {
        const [cards, bid] = line.split(' ');
        return new Game(
            cards.split('').filter((card => card.trim())),
            parseInt(bid.trim()),
        );
    });
}

function log(...args: unknown[]): void {
    if (debug) {
        console.log(...args);
    }
}

type Rank = {
    readonly rank: number;
    readonly cards?: number[],

    readonly five_of_a_kind?: number,   // rank: 6
    readonly four_of_a_kind?: number,   // rank: 5
    readonly full_house?: number[],     // rank: 4
    readonly three_of_a_kind?: number,  // rank: 3
    readonly two_pair?: number[],       // rank: 2
    readonly one_pair?: number,         // rank: 1

    readonly remainder?: number[],
};

function rank(game: Game): number {
    /**
     * Determine a rank. The rank is an object that instantly tells you what the hand represents -
     * five of a kind, four of a kind, etc -, the value of that sequence. And it has a remainder,
     * which are all the remaining cards not part of the sequence.
     *
     * The ranks are cached so speed up the process.
     */

    const cards = game.cards;

    // count chars:
    const per_char: Record<string, number> = {};
    for (const char of cards) {
        per_char[char] = (per_char[char] || 0) + 1;
    }

    const prepared = Object.entries(per_char).map(([card, count]) => ({ card, count }));
    prepared.sort((one, another) => another.count !== one.count
        ? another.count - one.count
        : 0);

    // has five of a kind?
    if (prepared.length === 1) {
        return 6;
    }

    // four of a kind?
    if (prepared.length === 2 && prepared[0].count === 4) {
        return 5;
    }

    // full house?
    if (prepared.length === 2 && prepared[0].count === 3 && prepared[1].count === 2) {
        return 4;
    }

    // three of a kind?
    if (prepared.length === 3 && prepared[0].count === 3) {
        return 3;
    }

    // two pair?
    if (prepared.length === 3 && prepared[0].count === 2) {
        return 2;
    }

    // one pair?
    if (prepared.length === 4) {
        return 1;
    }

    // high card
    return 0;
}
