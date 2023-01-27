const fs = require('fs');
const { argv } = require('process');
const { Stopwatch } = require('../stopwatch');

const sw = new Stopwatch();
const inputs = fs.readFileSync(`${__dirname}/${argv[2] || 'example'}.txt`, { encoding:'utf8', flag:'r' }).trim().split("\n");

// Read input
sw.start();

const operations = inputs.map(line => {
    const { operation, value } = line.match(/^(?<operation>nop|acc|jmp) (?<value>(\+\d+)|(-\d+))/).groups;
    return { operation, value: parseInt(value, 10), handled: false };
});
console.log(`Done reading input (${sw.elapsedMilliseconds()}ms)`);

const accumulate = (ops) => {
    let index = 0;
    let accumulator = 0;

    while (index < ops.length && ops[index].handled == false) {
        ops[index].handled = true;
        const { operation, value } = ops[index];

        switch (operation) {
            case 'nop':
                index++;
                break;
            case 'acc':
                index++;
                accumulator += value;
                break;
            case 'jmp':
                index += value;
                break;
        }
    }

    return [accumulator, index];
}

// Part 1
// Iterate over the commands while keeping track of the command that have already been handled. When we encounter
// a command we already handled, we exit. The point of attention is keeping track of the operations we already handled.
// In this solution I opted to administrate this inside the operation object. This does mean that in order to be abled 
// to reuse the operations list we need to make a deep clone. This is done by converting to JSON and parsing that again.
sw.restart();
const ops = JSON.parse(JSON.stringify(operations)); // make a clone without references
const [accumulator, _] = accumulate(ops);
console.log(`What value is in the accumulator? ${accumulator} (${sw.elapsedMilliseconds()}ms)`);

// Part 2
// We need to limit the search space. The only operations that are eligible for swapping are the operations that have been 
// handled in part 1 - we benefit from the choice of maintaining this inside the operation objects - and that have the 
// operation `jmp` or `nop`. That limits the number of eligible items from over 600 (input) to a little over 80 (for my
// input file at least). Maybe there is a smart way to determine what operation needs to be swapped, but trying them all
// runs between 10 and 30ms. That is good enough for now.
sw.restart();

const eligible = [...new Array(ops.length).keys()].filter(key => ops[key].operation != 'acc' && ops[key].handled);
let part_two = null;
for(let i = 0; i < eligible.length; i++) {
    const index = eligible[i];
    // Swap operations[index] from jmp to nop or vice versa 
    const o = JSON.parse(JSON.stringify(operations)); // make a clone without references
    o[index].operation = o[index].operation == 'jmp' ? 'nop' : 'jmp';

    // Calculate
    const [acc, last_index] = accumulate(o);
    if (last_index == operations.length) {
        part_two = acc;
        break;
    }
}

console.log(`What is the value of the accumulator after the program terminates? ${part_two} (${sw.elapsedMilliseconds()}ms)`);
