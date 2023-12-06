import { readFileSync } from 'fs';

const input = readFileSync(`${__dirname}/${process.argv[2] || 'example'}.txt`, { encoding:'utf8', flag:'r' }).trim();
const debug: boolean = !!(process.env.DEBUG || false);

const races = parse(input);

console.log('What do you get if you multiply these numbers together?', part_one(races));

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

type Race = {
    readonly time: number;
    readonly distance: number;
}

function parse(str: string): Race[] {
    const times = str.replace(/^.*Time:([\s0-9]+).*$/si, '$1').split(/\s+/i).filter(n => n.trim()).map(n => parseInt(n.trim()));
    const distances = str.replace(/^.*Distance:([\s0-9]+).*$/si, '$1').split(/\s+/i).filter(n => n.trim()).map(n => parseInt(n.trim()));

    return times.map((value, key) => ({ time: value, distance: distances[key] }));
}

function log(...args: unknown[]): void {
    if (debug) {
        console.log(...args);
    }
}
