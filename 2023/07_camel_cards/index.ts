import { readFileSync } from 'fs';
import { Stopwatch } from '../stopwatch';

const input = readFileSync(`${__dirname}/${process.argv[2] || 'example'}.txt`, { encoding:'utf8', flag:'r' }).trim().split("\n").map(line => line.trim());
const debug: boolean = !!(process.env.DEBUG || false);

const Type = {
    FIVE_OF_A_KIND: 6,
    FOUR_OF_A_KIND: 5,
    FULL_HOUSE: 4,
    THREE_OF_A_KIND: 3,
    TWO_PAIR: 2,
    ONE_PAIR: 1,
    HIGH_CARD: 0,
};

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

const card_ranks_wildcard = {
    '2': 2,
    '3': 3,
    '4': 4,
    '5': 5,
    '6': 6,
    '7': 7,
    '8': 8,
    '9': 9,
    'T': 10,
    'J': 0,
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

    public compareToPartTwo(another: Game): number {
        const r1 = rank_wildcard(this);
        const r2 = rank_wildcard(another);

        if (r1 != r2) {
            return r1 - r2;
        }

        // same type of hand, compare the cards from the left to the right
        for (let i = 0; i < this.cards.length; i++) {
            if (this.cards[i] !== another.cards[i]) {
                return card_ranks_wildcard[this.cards[i]] - card_ranks_wildcard[another.cards[i]];
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

{
    using sw = new Stopwatch('part two');
    console.log('What are the new total winnings?', part_two(games));
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
        log({
            cards: games[i].cards.join(''),
            type: ['high card', 'one pair', 'two pair', 'three of a kind', 'full house', 'four of a kind', 'five of a kind'][rank(games[i])],
        })
        sum += (i + 1) * games[i].bid;
    }

    return sum;
}

function part_two(games: Game[]): number {
    /**
     * We can reuse most of the code from part 1; the difference is that J can now be anything.
     * In the code to determine the type of hand I now also keep track of the number of occurrences
     * of the wildcard. Per type of hand can we than determine, using that number of occurrences,
     * how to make optimal use of the wildcard(s). When sorting the hands, also consider `J` as 0.
     */
    games.sort((one, another) => one.compareToPartTwo(another));

    let sum = 0;
    for(let i = 0; i < games.length; i++) {
        if (games[i].cards.includes('J'))
            log({
                cards: games[i].cards.join(''),
                type: ['high card', 'one pair', 'two pair', 'three of a kind', 'full house', 'four of a kind', 'five of a kind'][rank_wildcard(games[i])],
            })
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

function rank_wildcard(game: Game): number {
    /**
     * Determine a rank. The rank is an object that instantly tells you what the hand represents -
     * five of a kind, four of a kind, etc -, the value of that sequence. And it has a remainder,
     * which are all the remaining cards not part of the sequence.
     *
     * The ranks are cached so speed up the process.
     */

    const cards = game.cards;
    const wildcard = 'J';

    // count chars:
    const per_char: Record<string, number> = {};
    let num_of_wildcards = 0;
    for (const char of cards) {
        if (char === wildcard || false) {
            num_of_wildcards++;
        }
        per_char[char] = (per_char[char] || 0) + 1;
    }

    const prepared = Object.entries(per_char).map(([card, count]) => ({ card, count }));
    prepared.sort((one, another) => another.count !== one.count
        ? another.count - one.count
        : 0);

    // has five of a kind?
    if (prepared.length === 1) {
        return Type.FIVE_OF_A_KIND;
    }

    // four of a kind?
    if (prepared.length === 2 && prepared[0].count === 4) {
        // AAAAK
        // AAAAJ
        // JJJJA

        // Can we improve with the wildcard?
        if (
            num_of_wildcards === 1
            || num_of_wildcards === 4
        ) {
            return Type.FIVE_OF_A_KIND;
        }

        return Type.FOUR_OF_A_KIND;
    }

    // full house?
    if (prepared.length === 2 && prepared[0].count === 3 && prepared[1].count === 2) {
        // AAAKK
        // AAAJJ
        // JJJAA

        if (
            (num_of_wildcards === 2 && prepared[0].card !== wildcard)    // AAAJJ
            || (num_of_wildcards === 3 && prepared[1].card !== wildcard) // JJJAA
        ) {
            return Type.FIVE_OF_A_KIND;
        }
        return Type.FULL_HOUSE;
    }

    // three of a kind?
    if (prepared.length === 3  && prepared[0].count === 3) {
        // AAAKQ
        // AAAJK
        // JJJAK

        // Can we improve with the wildcard?
        if (num_of_wildcards === 1      // AAAJK
            || num_of_wildcards === 3   // JJJAK
        ) {
            return Type.FOUR_OF_A_KIND;
        }

        return Type.THREE_OF_A_KIND;
    }

    // two pair?
    if (prepared.length === 3 && prepared[0].count === 2) {
        // AAKKQ
        // AAKKJ
        // AAJJK

        if (num_of_wildcards === 2) {   // AAJJK
            return Type.FOUR_OF_A_KIND;
        }
        if (num_of_wildcards === 1) {   // AAKKJ
            return Type.FULL_HOUSE
        }

        return Type.TWO_PAIR;
    }

    // one pair?
    if (prepared.length === 4) {
        // AAKQT
        // AAKQJ
        // JJAKQ

        if (num_of_wildcards === 2) {   // JJAKQ
            return Type.THREE_OF_A_KIND;
        }
        if (num_of_wildcards === 1) {   // AKQQJ
            return Type.THREE_OF_A_KIND;
        }

        return Type.ONE_PAIR;
    }

    // high card
    // AKQT9
    // AKQJT
    if (num_of_wildcards === 1) {      // AKQJT
        return Type.ONE_PAIR;
    }

    return Type.HIGH_CARD;
}
