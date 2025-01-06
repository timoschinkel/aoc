import { readFileSync } from 'fs';
import { Stopwatch } from '../stopwatch';

const input = readFileSync(`${__dirname}/${process.argv[2] || 'example'}.txt`, { encoding:'utf8', flag:'r' }).trim().split("\n").map(line => line.trim());
const debug: boolean = !!(process.env.DEBUG || false);

interface Module {
    send(source: string, high: boolean): Pulse[];
    addSource(source: string): void;
}

class FlipFlop implements Module {
    private on: boolean = false;
    constructor(private readonly name: string, private readonly targets: string[]) {}

    send(source: string, high: boolean): Pulse[] {
        if (high) { // If a flip-flop module receives a high pulse, it is ignored and nothing happens.
            return [];
        }

        this.on = !this.on; // flip
        return this.targets.map((target) => ({ from: this.name, high: this.on, to: target }));
    }

    addSource(source: string): void {}
}

class Conjunction implements Module {
    private sources: Record<string, boolean> = {};
    constructor(private readonly name: string, private readonly targets: string[]) {}

    send(source: string, high: boolean): Pulse[] {
        this.sources[source] = high;

        const all_on = Object.values(this.sources).filter(s => s === true).length === Object.keys(this.sources).length;

        return this.targets.map((target) => ({ from: this.name, high: !all_on, to: target }));
    }

    addSource(source: string): void {
        this.sources[source] = false;
    }
}

class Broadcaster implements Module {
    constructor(private readonly name: string, private readonly targets: string[]) {}

    send(source: string, high: boolean): Pulse[] {
        return this.targets.map((target) => ({ from: this.name, high, to: target }));
    }

    addSource(source: string): void {}
}

const modules = parse(input);

{
    using sw = new Stopwatch('part one');
    console.log('What are the total winnings?', part_one({ ...modules }));
}

{
    using sw = new Stopwatch('part two');
    console.log('What are the new total winnings?', part_two());
}

function part_one(modules: Modules): number {
    /**
     * I did not solve this puzzle during the 2023 Advent of Code. I even did not solve it the following year. It took
     * me well over a year. The main reason is that I misread the puzzle completely. The sentence that I completely
     * misread was:
     *
     * > If a flip-flop module receives a high pulse, it is ignored and nothing happens.
     *
     * I was struggling to replicate the example with pen and paper. After finishing Advent of Code 2024 I went back to
     * the puzzles of 2023. I looked into the solutions thread on [Reddit][reddit] for inspiration. I found a solution
     * that also had an [explanation][explanation]. Reading that solution dropped the penny for me, and I was able to
     * replicate the examples using pen and paper. Implementation for that point was straightforward.
     *
     * Every pulse is an object, the wording "Pulses are always processed in the order they are sent." indicates some
     * form of queueing. Because the modules have state, I represented them as classes. Read a pulse from the queue,
     * send it to the correct module, and add the resulting pulses to the queue again.
     *
     * [reddit]: https://www.reddit.com/r/adventofcode/comments/18mmfxb/2023_day_20_solutions/
     * [explanation]: https://advent-of-code.xavd.id/writeups/2023/day/20/
     */

    let high = 0;
    let low = 0;

    for (let i = 0; i < 1000; i++) {
        low++; // press button
        log('button -low-> broadcaster');

        const queue = modules.broadcaster.send('button', false);

        while (queue.length > 0) {
            const pulse = queue.shift();

            if (pulse.high) {
                high++;
            } else {
                low++;
            }

            log(`${pulse.from} -${pulse.high?'high':'low'}-> ${pulse.to}`);

            if (!(pulse.to in modules)) {
                continue;
            }

            const module = modules[pulse.to];
            queue.push(...module.send(pulse.from, pulse.high));
        }
    }

    log('high', high, 'low', low);
    return high * low;
}

function part_two(): number {
    return 0;
}

type Pulse = {
    readonly from: string;
    readonly high: boolean;
    readonly to: string;
}

type Modules = {
    [key: string]: Module;
}

function parse(input: string[]): Modules {
    const modules = {};
    const conjunctions: string[] = [];

    // iterate over the input and convert to correct objects
    for (let i = 0; i < input.length; i++) {
        const matches = input[i].match(/^(?<type>%|&)?(?<name>[^\s]+) -> (?<targets>.+)$/);
        if (matches.groups.type === '%') {
            modules[matches.groups.name] = new FlipFlop(matches.groups.name, matches.groups.targets.split(', '));
        } else if (matches.groups.type === '&') {
            modules[matches.groups.name] = new Conjunction(matches.groups.name, matches.groups.targets.split(', '));
            conjunctions.push(matches.groups.name);
        } else if (matches.groups.name === 'broadcaster') {
            modules[matches.groups.name] = new Broadcaster(matches.groups.name, matches.groups.targets.split(', '));
        } else {
            console.log('Found unknown type', input[i]);
            process.exit(1);
        }
    }

    // second pass to add all sources to conjunctions
    for (let i = 0; i < input.length; i++) {
        const row = input[i];
        const matches = row.match(/^(?<type>%|&)?(?<name>[^\s]+) -> (?<targets>.+)$/);
        const targets = matches.groups.targets.split(', ');

        targets.forEach(target => {
            if (conjunctions.includes(target)) {
                modules[target].addSource(matches.groups.name);
            }
        })

    }

    return modules;
}

function log(...args: unknown[]): void {
    if (debug) {
        console.log(...args);
    }
}
