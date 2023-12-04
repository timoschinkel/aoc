import { readFileSync } from 'fs';

const input = readFileSync(`${__dirname}/${process.argv[2] || 'example'}.txt`, { encoding:'utf8', flag:'r' }).trim().split("\n");
const debug: boolean = !!(process.env.DEBUG || false);

const cards = input.map((line) => parse(line));

console.log('How many points are they worth in total?', part_one(cards));

function part_one(cards: Card[]): number {
    /*
     * Very straightforward; parse the cards, take the intersection between the numbers
     * on the card and the winners. That is the number of matches. The trick here is that
     * doubling is the power of 2, but we're starting at 1 instead of at 2. Keeping in
     * mind that <any number>^0 equals 1, we can apply a small trick; the score for a card
     * equals the number of 2^<number of matches - 1>
     */
    let sum = 0;

    for (const card of cards) {
        const matches = intersect(card.winners, card.on_card);

        if (matches.length > 0) {
            sum += Math.pow(2, matches.length - 1);
        }
    }

    return sum;
}

type Card = {
    readonly winners: number[],
    readonly on_card: number[],
}

function parse(str: string): Card {
    const [_, numbers] = str.split(':');
    const [ winners, on_card ] = numbers.split('|');

    return {
        winners: winners.trim().split(/\s+/si).map((n) => parseInt(n.trim())),
        on_card: on_card.trim().split(/\s+/si).map((n) => parseInt(n.trim())),
    }
}

function intersect(one: unknown[], another: unknown[]): unknown[] {
    return one.filter((value) => another.includes(value));
}

function log(...args: unknown[]): void {
    if (debug) {
        console.log(...args);
    }
}
