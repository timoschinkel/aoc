const fs = require('fs');
const { argv } = require('process');
const { Stopwatch } = require('../stopwatch');

const sw = new Stopwatch();
const inputs = fs.readFileSync(`${__dirname}/${argv[2] || 'example'}.txt`, { encoding:'utf8', flag:'r' }).trim().split("\n\n");

// Part 1
// The puzzle effectively is about finding the number of unique values in all answer lines. Here the limited number of methods
// on JavaScript arrays becomes visible. But we can extend the Array prototype with a `unique()` method. This is mostly a
// readibility approach.

sw.start();

Array.prototype.unique = function () {
    return [...new Set(this)];
}

let part_one = 0;
inputs.forEach(entry => {
    const answers = entry
        .split("\n")
        .reduce((carry, value) => carry.concat(Array.from(value)), [])
        .unique();

    part_one += answers.length;
});

console.log(`What is the sum of those counts? ${part_one} (${sw.elapsedMilliseconds()}ms)`);

// Part 2
// Part 2 is similar to part 1, but instead of finding the unique values we need the intersection between
// the answer lines. 

/**
 * @param {Array} other 
 * @returns {Array}
 */
Array.prototype.intersect = function(other) {
    return this.filter(entry => other.includes(entry));
}

sw.restart();
let part_two = 0;
inputs.forEach(entry => {
    const answers = entry
        .split("\n")
        .reduce((carry, value) => {
            return carry == null ? Array.from(value) : carry.intersect(Array.from(value));
        }, null);

    part_two += answers.length;
});

console.log(`What is the sum of those counts? ${part_two} (${sw.elapsedMilliseconds()}ms)`);
