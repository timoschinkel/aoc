import { readFileSync } from 'fs';

const input = readFileSync(`${__dirname}/${process.argv[2] || 'example'}.txt`, { encoding:'utf8', flag:'r' }).trim().split("\n");
const debug: boolean = !!(process.env.DEBUG || false);

const cards = input.map((line) => parse(line));

console.log('How many points are they worth in total?', part_one(cards));
console.log('How many total scratchcards do you end up with?', part_two(cards));

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

function part_two(cards: Card[]): number {
    /*
     * I head to read carefully to understand the problem. I altered the parsing
     * to also include the card id and the number of copies of the card, defaulting
     * to 1. Now iterate over the cards, determine the number of winners and add
     * the number of copies of the current card to the following card; if you have 2
     * copies of a card that wins 1 card, then you need to add a copy to the next card
     * **for every copy of the current card**.
     *
     * Don't forget to not go out of bounds :)
     */
    for (const card of cards) {
        const matches = intersect(card.winners, card.on_card);

        for(let c = card.id; c < Math.min(card.id + matches.length, cards.length); c++) {
            cards[c].count += card.count;
        }
    }

    return cards.reduce((sum, card) => sum + card.count, 0);
}

type Card = {
    readonly winners: number[],
    readonly on_card: number[],
    readonly id: number,
    count: number,
}

function parse(str: string): Card {
    const [head, numbers] = str.split(':');
    const [ winners, on_card ] = numbers.split('|');

    return {
        winners: winners.trim().split(/\s+/si).map((n) => parseInt(n.trim())),
        on_card: on_card.trim().split(/\s+/si).map((n) => parseInt(n.trim())),
        id: parseInt(head.replace('Card ', '').trim()),
        count: 1,
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
