import { readFileSync } from 'fs';
import { Stopwatch } from '../stopwatch';

const input = readFileSync(`${__dirname}/${process.argv[2] || 'example'}.txt`, { encoding:'utf8', flag:'r' }).trim().split("\n").map(line => line.trim());
const debug: boolean = !!(process.env.DEBUG || false);

const slabs = parse(input);

{
    using sw = new Stopwatch('part one');
    console.log('How many bricks could be safely chosen as the one to get disintegrated?', part_one(slabs));
}

{
    using sw = new Stopwatch('part two');
    console.log('What are the new total winnings?', part_two());
}


function part_one(slabs: Slab[]): number {
    /**
     * Let's play Jenga[0]! I need to thank Reddit user Kullu00[1] for an important
     * observation; in the example input the slabs are sorted on ascending z value,
     * but in the puzzle input they're not. After reading the puzzle text again, and
     * glancing over some nice visualizations on Reddit[2] made the puzzle click for
     * me.
     *
     * By sorting the input on ascending z values the puzzle becomes easier; we now
     * can drop the slabs one by one and store the occupied positions in a 3D grid.
     * Next step is to determine if a slab can be desintegrated safely. This caused
     * some confusion, but by making a drawing, and with the help of some additional
     * example inputs[4], I was able to find working logic. I perform the logic on
     * every block drop. Maybe it is more efficient do iterate over all blocks after
     * they all fell. But I am not complaining with a solution in 150ms.
     *
     * [0]: https://en.wikipedia.org/wiki/Jenga
     * [1]: https://www.reddit.com/r/adventofcode/comments/18oboe8/comment/keg5zn8/?utm_source=share&utm_medium=web2x&context=3
     * [2]: https://www.reddit.com/r/adventofcode/comments/18obzrf/2023_day_22_falling_with_style/
     * [4]: https://www.reddit.com/r/adventofcode/comments/18oboe8/2023_day_22_part_1/
     */
    const board: Record<string, string> = {};

    const can_drop = (slab: Slab): boolean => {
        const z = slab.from.z;
        if (z <= 1) {
            return false;
        }

        for (let x = slab.from.x; x <= slab.to.x; x++) {
            for (let y = slab.from.y; y <= slab.to.y; y++) {
                if (`${x}, ${y}, ${z-1}` in board) {
                    return false;
                }
            }
        }

        return true;
    }

    // sort slabs on z
    slabs.sort((one, another) => one.from.z - another.from.z);

    // a dictionary indexed by the supporting slab and what other slabs it is supporting
    const supports: Record<string, string[]> = {};

    // a dictionary indexed by the supported slab, and by what slabs it is being supported
    const is_supported_by: Record<string, string[]> = {};

    const desintegratable: Record<string, boolean> = {};

    for (const slab of slabs) {
        // place slab tetris style and see if it gets blocked
        const s = slab.id;

        while (can_drop(slab)) {
            slab.from.z--;
            slab.to.z--;
        }

        supports[s] = [];

        // We can no longer drop, we fixate the slab and check on what other slabs it might be leaning
        for (let x = slab.from.x; x <= slab.to.x; x++) {
            for (let y = slab.from.y; y <= slab.to.y; y++) {
                // check if there's a slab below us
                const underlying = board[`${x}, ${y}, ${slab.from.z - 1}`] ?? undefined;
                if (underlying && !(is_supported_by[s] ?? []).includes(underlying)) { // we have found a connection!
                    is_supported_by[s] = [ ...(is_supported_by[s] ?? []), underlying];
                    supports[underlying] = [ ...(supports[underlying] ?? []), s];
                }

                for (let z = slab.from.z; z <= slab.to.z; z++) {
                    board[`${x}, ${y}, ${z}`] = s;
                }
            }
        }

        // check the slabs whether they can be desintegrated
        desintegratable[s] = true; // new slabs can be desintegrated by default

        for (const supporter of (is_supported_by[s] ?? [])) {
            // check how many other slabs the supporter is supporting
            let can_be_desintegrated = true;
            for (const is_supporting of (supports[supporter] ?? [])) {
                can_be_desintegrated = can_be_desintegrated && (is_supported_by[is_supporting] ?? []).length > 1
            }

            desintegratable[supporter] = can_be_desintegrated;
        }
    }
    return Object.values(desintegratable).reduce((carry, value) => carry + (value ? 1 : 0), 0);
}

function part_two(): number {
    return 0;
}

type Point = {
    x: number;
    y: number;
    z: number;
}

type Slab = {
    readonly id: string;
    readonly from: Point;
    readonly to: Point;
}

function parse(input: string[]): Slab[] {
    return input.map((line, index) => {
        const coords = line.split(/~|,/si).map(i => parseInt(i));
        return {
            id: index.toString(), //String.fromCharCode(65 + index),
            from: { x: coords[0], y: coords[1], z: coords[2] },
            to: { x: coords[3], y: coords[4], z: coords[5] }
        }
    });
}

function log(...args: unknown[]): void {
    if (debug) {
        console.log(...args);
    }
}
