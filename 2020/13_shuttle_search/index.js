const fs = require('fs');
const { argv } = require('process');
const { Stopwatch } = require('../stopwatch');

const sw = new Stopwatch();
const inputs = fs.readFileSync(`${__dirname}/${argv[2] || 'example'}.txt`, { encoding:'utf8', flag:'r' }).trim().split("\n");

const earliest_departure = parseInt(inputs[0], 10);
const busses = inputs[1].split(',').map(i => i == 'x' ? 0 : parseInt(i, 10));

// Part 1
// In order to know what bus will arrive first we need to calculate the waiting time and take the bus with the shortest waiting time.
// To calculate the waiting time we can take the remainder - modulo - of the time we want to depart and subtract that from the bus 
// number / travel time.
sw.start();
const earliest_bus = busses.filter(bus => bus > 0).reduce(
    (carry, value) => {
        const waiting_time = value - earliest_departure % value;
        return waiting_time < carry.waiting_time
            ? { bus: value, waiting_time }
            : carry;
    }, 
    {bus: busses[0], waiting_time: busses[0] - earliest_departure % busses[0]}
);

const part_one = earliest_bus.bus * earliest_bus.waiting_time;
console.log(`What is the ID of the earliest bus you can take to the airport multiplied by the number of minutes you'll need to wait for that bus? ${part_one} (${sw.elapsedMilliseconds()}ms)`);

// Part 2
// This one took a lot of my mathematical skills. We know that the answer is a multiple of the first bus, because the answer is a moment when the first bus departs. 
// My first approach was to iterate over all numbers while incrementing with the first bus. That works very well for the example, but not for the input. We need
// to optimize the way we find the answer. One thing I noticed is that the bus numbers are prime numbers. Going from experience with other AoC puzzles this often
// indicates using LCM, which with prime numbers of simply multiplying them together. I experimented a lot with this, but it wasn't until I received some pointers
// from https://twitter.com/DylanMeeus/status/1338260007339626496 that the penny dropped. 
// We can find the increment value by calculating the LCM of the previous busses. For the second bus that means incrementing with the value of the first bus, for
// the third bus we need to increment with the value of bus 1 and 2. This will make the steps increasingly big, and that means we're moving towards the answer 
// a lot quicker.

sw.restart();

const is_valid = (value, to_check) => {
    for (let i = 0; i < to_check.length; i++) {
        const bus = to_check[i];
        if (bus == 0) continue;
        if ((value + i) % bus != 0) return false;
    }
    return true;
};

let t = busses[0];
for (let i = 2; i <= busses.length; i++) {
    if (busses[i - 1] == 0) continue;

    const increment = busses.slice(0, i - 1).reduce((c, v) => v == 0 ? c : c * v, 1);
    const slice = busses.slice(0, i);
    while (is_valid(t, slice) == false) {
        t += increment;
    }
}

console.log(`What is the earliest timestamp such that all of the listed bus IDs depart at offsets matching their positions in the list? ${t} (${sw.elapsedMilliseconds()}ms)`);
