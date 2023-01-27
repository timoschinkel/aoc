const fs = require('fs');
const { argv } = require('process');
const { Stopwatch } = require('../stopwatch');

const sw = new Stopwatch();
const inputs = fs.readFileSync(`${__dirname}/${argv[2] || 'example'}.txt`, { encoding:'utf8', flag:'r' }).trim().split("\n").map(line => parseInt(line, 10));
const preamble = parseInt(argv[3] || 25, 10);

// Part 1
// I started what I felt was a brute force approach, but it turns out it runs in 1 millisecond. So maybe it is not as brute force as I thought.
// Starting at index = preamble size I iterate over all the numbers in the input. To get the preamble I take the previous "preamble size" list 
// of numbers. Because we need a pair I then iterate over the preamble, calculate what number I need to add up to the goal and search the preamble
// for that number. If we find a match we can do an early exit. If we find the preamble is invalid we can also exit early.
sw.start();
let part_one = null;
for(let index = preamble; index < inputs.length; index++) {
    // check if valid:
    const goal = inputs[index];
    const xmas = [...inputs.slice(index - preamble, index)];

    const is_valid = () => {
        // iterate over xmas until we find a valid match, or until we are out of bounds
        let i = 0;
        while (i < xmas.length) {
            // We know we need a pair, so we can calculate which number would form a valid pair with the current
            const remainder = goal - xmas[i];
            if (xmas.includes(remainder)) {
                return true;
            }
            i++;
        }
        return false;
    }

    if (is_valid()) {
        continue;
    } else {
        // we found the invalid number! Register and exit early.
        part_one = inputs[index];
        break;
    }
}

console.log(`What is the first number that does not have this property? ${part_one} (${sw.elapsedMilliseconds()}ms)`);

// Part 2
// For every element in the input add up all contiguous numbers while the sum is smaller than the answer from part 1, or we 
// run out of items in our list. If the sum after this is equal to the answer from part 1 we return the length of the 
// continguous set. We can take the subset and take the highest and lowest value.
sw.restart();
let part_two = null;
for (let index = 0; index < inputs.length; index++) {
    const contiguous = () => {
        let sum = inputs[index];
        let length = 1;
        while (sum < part_one && index + length < inputs.length) {
            sum += inputs[index + length];
            length++;
        }

        return sum == part_one ? length : 0;
    }

    const length = contiguous();
    if (length > 1) {
        // Array.prototype.sort() is an in-place operation. In order to maintain the integrity of the original input
        // I use a trick to ensure a clone without references by converting and parsing JSON.
        const set = JSON.parse(JSON.stringify(inputs.slice(index, index + length)));

        // Sort in place. Array.prototype.sort() by default uses natural sorting, so to make it work as expected on
        // numeric values we need to specify a comparison function.
        set.sort((one, another) => one - another);

        const min = set[0], max = set[set.length - 1];
        part_two = min + max;
        break;
    }
}

console.log(`What is the encryption weakness in your XMAS-encrypted list of numbers? ${part_two} (${sw.elapsedMilliseconds()}ms)`);
