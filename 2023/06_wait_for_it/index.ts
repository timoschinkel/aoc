import { readFileSync } from 'fs';
import { Stopwatch } from '../stopwatch';

const input = readFileSync(`${__dirname}/${process.argv[2] || 'example'}.txt`, { encoding:'utf8', flag:'r' }).trim();
const debug: boolean = !!(process.env.DEBUG || false);

const races = parse(input);
const race = parse_single(input);

{
    using sw = new Stopwatch('part one');
    console.log('What do you get if you multiply these numbers together?', part_one(races));
}

{
    using sw = new Stopwatch('part two');
    console.log('How many ways can you beat the record in this one much longer race?', part_two(race))
}

function part_one(input: Race[]): number {
    /*
     * There is probably a very smart way to limit the possible press times, but just
     * iterating over all possible values was efficient enough.
     */
    const distance = (race: Race, pressed: number): number => {
        return (race.time - pressed) * pressed;
    }

    const number_of_ways_to_beat = input.map((race): number => {
        log('Race', race);
        let num = 0;
        for (let i = 0; i < race.time; i++) {
            if (distance(race, i) > race.distance) {
                log(`Found a winner by pressing for ${i}`);
                num++;
            }
        }

        return num;
    });

    return number_of_ways_to_beat.reduce((carry, value) => carry * value, 1);
}

function part_two(game: Race): number {
    /*
     * Let's try to optimize this; we know that we will win within a range of
     * pressing the button. So, we can search for the start and the end of this
     * range. Once we did that we can count how many numbers are in that range.
     */

    const distance = (race: Race, pressed: number): number => {
        return (race.time - pressed) * pressed;
    }

    let lower_boundary = 0;
    while (distance(race, lower_boundary) < race.distance) {
        lower_boundary++;
    }

    let upper_boundary = race.time;
    while (distance(race, upper_boundary) < race.distance) {
        upper_boundary--;
    }

    return upper_boundary - lower_boundary + 1;
}

type Race = {
    readonly time: number;
    readonly distance: number;
}

function parse(str: string): Race[] {
    const times = str.replace(/^.*Time:([\s0-9]+).*$/si, '$1').split(/\s+/i).filter(n => n.trim()).map(n => parseInt(n.trim()));
    const distances = str.replace(/^.*Distance:([\s0-9]+).*$/si, '$1').split(/\s+/i).filter(n => n.trim()).map(n => parseInt(n.trim()));

    return times.map((value, key) => ({ time: value, distance: distances[key] }));
}

function parse_single(str: string): Race {
    return {
        time: parseInt(str.replace(/^.*Time:([\s0-9]+).*$/si, '$1').trim().replaceAll(/[^0-9]+/sgi, '')),
        distance: parseInt(str.replace(/^.*Distance:([\s0-9]+).*$/si, '$1').trim().replaceAll(/[^0-9]+/sgi, '')),
    };
}

function log(...args: unknown[]): void {
    if (debug) {
        console.log(...args);
    }
}


