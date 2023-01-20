const fs = require('fs');
const { argv } = require('process');
const { Stopwatch } = require('../stopwatch');

const sw = new Stopwatch();
const input = fs.readFileSync(`${__dirname}/${argv[2] || 'example'}.txt`, { encoding:'utf8', flag:'r' }).trim().split("\n");
const debug = (argv[3] || '') == 'debug';

// Part 1
// Approach is relatively straight forward; The input is parsed using a regular expression. Using `Array.from()` I convert 
// the input to an array of characters. After that we can count the number of occurrences of the requested character.
sw.start();
const invalid = input.reduce((carry, line) => {
    const [, min, max, search, password] = /^(?<min>[0-9]+)-(?<max>[0-9]+) (?<search>[a-z]+): (?<password>[a-z]+)$/s.exec(line);
    const occurrences = Array.from(password).filter(c => c == search).length;
    return carry + (occurrences >= min && occurrences <= max ? 1 : 0)
}, 0);

console.log(`How many passwords are valid according to their policies? ${invalid} (${sw.elapsedMilliseconds()}ms)`);

