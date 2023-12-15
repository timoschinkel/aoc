const fs = require('fs');
const { argv } = require('process');
const { Stopwatch } = require('../stopwatch');

const sw = new Stopwatch();
const inputs = fs.readFileSync(`${__dirname}/${argv[2] || 'example'}.txt`, { encoding:'utf8', flag:'r' }).trim().split("\n");

const height = inputs.length, width = inputs[0].length;

// Part 1
// The keys to this puzzle are "when the chaos stabilizes" and "rules are applied to every seat simultaneously". So we need to keep 
// track of the previous state to be able to check if the chaos stabilized. And we need to inspect the state while building up a 
// new state. With these two things we can simply implement that rules as they are and the solution runs in 100 - 150ms, which can 
// maybe be optimized, but for now is acceptable.
sw.start();
let previous_state = '';
let state = JSON.parse(JSON.stringify(inputs)); // create a clone without references

while (state.join("\n") != previous_state) {
    previous_state = state.join("\n");
    new_state = [];

    const count_occupied = (x, y) => {
        const occupied = (dx, dy) => {
            if (x + dx < 0 || x + dx >= width || y + dy < 0 || y + dy >= height) return false;

            return state[y + dy][x + dx] == '#';
        }

        // The delta approach works really well in having both readible code and allowing splitting logic to 
        // a separate function.
        return [ 
            occupied(-1, -1), occupied(0, -1), occupied(1, -1),
            occupied(-1, 0) ,                , occupied(1, 0),
            occupied(-1, 1) , occupied(0, 1) , occupied(1, 1),
        ].filter(o => o).length;
    }

    for (let y = 0; y < height; y++) {
        let row = '';
        for (let x = 0; x < width; x++) {
            const seat = state[y][x];
            if (seat == 'L' && count_occupied(x, y) == 0) {
                row += '#';
            } else if (seat == '#' && count_occupied(x, y) >= 4) {
                row += 'L';
            } else {
                row += seat;
            }
        }
        new_state.push(row);
    }

    state = new_state;
}

const part_one_occupied = state.reduce((carry, value) => carry + Array.from(value).reduce((c, v) => c + (v == '#' ? 1 : 0), 0), 0);
console.log(`How many seats end up occupied? ${part_one_occupied} (${sw.elapsedMilliseconds()}ms)`);

// Part 2
// We can reuse the solution from part 1, with the exception that the `count_occupied()` function needs to be modified to keep
// walking into a direction until either a '#' or an 'L' is encountered. Again there might be some optimization by adding caching,
// but with approx. 200ms runtime it did not seem worth it.
sw.restart();
previous_state = '';
state = JSON.parse(JSON.stringify(inputs));

while (state.join("\n") != previous_state) {
    previous_state = state.join("\n");
    new_state = [];

    const count_occupied = (x, y) => {
        const occupied = (dx, dy) => {
            const sx = dx, sy = dy;
            while(x + dx >= 0 && x + dx < width && y + dy >= 0 && y + dy < height && state[y + dy][x + dx] == '.') {
                dx += sx;
                dy += sy;
            }

            return (x + dx < 0 || x + dx >= width || y + dy < 0 || y + dy >= height)
                ? false
                : state[y + dy][x + dx] == '#';
        }

        return [
            occupied(-1, -1), occupied(0, -1), occupied(1, -1),
            occupied(-1, 0) ,                , occupied(1, 0),
            occupied(-1, 1) , occupied(0, 1) , occupied(1, 1),
        ].filter(o => o).length;
    }

    for (let y = 0; y < height; y++) {
        let row = '';
        for (let x = 0; x < width; x++) {
            const seat = state[y][x];
            if (seat == 'L' && count_occupied(x, y) == 0) {
                row += '#';
            } else if (seat == '#' && count_occupied(x, y) >= 5) {
                row += 'L';
            } else {
                row += seat;
            }
        }
        new_state.push(row);
    }

    state = new_state;
}

const part_two_occupied = state.reduce((carry, value) => carry + Array.from(value).reduce((c, v) => c + (v == '#' ? 1 : 0), 0), 0);
console.log(`How many seats end up occupied? ${part_two_occupied} (${sw.elapsedMilliseconds()}ms)`);
