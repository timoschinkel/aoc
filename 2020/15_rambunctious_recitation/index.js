const fs = require('fs');
const { argv, setUncaughtExceptionCaptureCallback } = require('process');
const { Stopwatch } = require('../stopwatch');

const sw = new Stopwatch();
const inputs = fs.readFileSync(`${__dirname}/${argv[2] || 'example'}.txt`, { encoding:'utf8', flag:'r' }).trim().split(",").map(line => parseInt(line, 10));
const number_of_rounds = parseInt(argv[3] || '2020', 10);

const get_last_spoken = (iterations) => {

    // Inspiration: https://github.com/Wufflez/AdventOfCode-2020/blob/master/day-15-cs/Program.cs

    // Instantiate an array. This will prevent on the fly memory allocation, which leads to a hefty performance penalty
    const numbers_spoken = new Array(iterations).fill(0);

    // Put in the input
    let iteration, last;
    for (iteration = 0; iteration < inputs.length; iteration++) {
        numbers_spoken[inputs[iteration]] = iteration + 1;
        last = inputs[iteration];
    }

    // Iterate until we have the required number of iterations
    for (; iteration < iterations; iteration++) {
        // We are lagging so to speak; We want to know not only the last time we saw a number, but the time before that.
        const previous = numbers_spoken[last];
        const next = previous != 0 ? iteration - previous : 0;
        numbers_spoken[last] = iteration;
        last = next;
    }

    return last;
}

// Part 1
// Simply implement the rules; Maintain a dictionary with the spoken numbers as index, and an array of occurrences as values.
sw.start();
const part_one = get_last_spoken(2020);
console.log(`What will be the 2020th number spoken? ${part_one} (${sw.elapsedMilliseconds()}ms)`);

// Part 2
// As usual applying the solution of part 1 on part 2 did not work as expected; It takes approx. 14 minutes to solve the puzzle. This is 
// due to constant reallocation of memory caused by array operations. The solution in those situations is to simplify the approach to only
// keep simple values. So initialize an array with a fixed size - no more dynamic memory allocation - and rework the "timing" of the code 
// so you only need to store one number instead of the last and second to last. It's still not fast - 1.5 minutes - but it is fast enough.
sw.restart();
const part_two = get_last_spoken(30000000);
console.log(`What will be the 30000000th number spoken? ${part_two} (${sw.elapsedMilliseconds()}ms)`);
