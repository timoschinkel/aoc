const fs = require('fs');
const { argv } = require('process');
const { Stopwatch } = require('../stopwatch');

const sw = new Stopwatch();
const input = fs.readFileSync(`${__dirname}/${argv[2] || 'example'}.txt`, { encoding:'utf8', flag:'r' }).trim().split("\n");

const seat_id = (location) => {
    let range = [...Array(128).keys()];
    for (let i = 0; i < 7; i++) {
        range = location[i] == 'F'
            ? range.slice(0, range.length / 2)
            : range.slice(range.length / 2);
    }
    const row = range[0];

    // find column
    range = [...Array(8).keys()];
    for(let i = 7; i < 10; i++) {
        range = location[i] == 'L'
            ? range.slice(0, range.length / 2)
            : range.slice(range.length / 2);
    }
    const column = range[0];

    return row * 8 + column;
}

// Part 1
// Convert all locations to seat ids, sort ascending, take the last item in the array.
// The most heavy part of this is calculating the seat id. We can optimize this by adding cache. Another optimization is to order the locations 
// before converting them to seat ids. Because F comes after the B in the alphabet sorting the locations alphabetically ensures that the max
// location is in the first 1 to 7 items. So we only have to calculate the seat id for this locations. That shaves off 6 milliseconds, but it 
// introduces some complexity and having all seat ids makes part 2 really simple.

sw.start();
const seats = input.map(location => seat_id(location));
seats.sort((a, b) => a - b);
console.log(`What is the highest seat ID on a boarding pass? ${seats[seats.length - 1]} (${sw.elapsedMilliseconds()}ms)`);

// Part 2
// We can reuse the ordered list of seat ids from part 1. Iterate over the list and compare the previous with the current seat id. This works 
// straight forward and by adding a `break` we exit as soon as possible.

sw.restart();
let missing = null;
for(let i = 1; i < seats.length; i++) {
    if (seats[i] != seats[i - 1] + 1) {
        missing = seats[i - 1] + 1;
        break;
    }
}

console.log(`Missing: ${missing} (${sw.elapsedMilliseconds()}ms)`); // 101, 6ms

