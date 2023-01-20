const fs = require('fs');
const { argv } = require('process');
const { Stopwatch } = require('../stopwatch');

const sw = new Stopwatch();
const input = fs.readFileSync(`${__dirname}/${argv[2] || 'example'}.txt`, { encoding:'utf8', flag:'r' }).trim().split("\n\n");

// Part 1
// Mostly string manipulation. The difficulty here lies in the order of the elements, making a regular expression cumbersome. My
// solution is simple string splitting; Split by whitespace to get all the pairs, split every pair by `:` to separate the key 
// from the value, and take only the key. By filtering the keys against the fixed set of properties we end up with a list with
// only valid keys. If that list contains 7 items, the entry is valid.
sw.start();
let valid = 0;
input.forEach(entry => {
    const keys = entry
        .split(/\s+/)
        .map(pair => pair.split(':')[0])
        .filter(key => ['byr', 'iyr', 'eyr', 'hgt', 'hcl', 'ecl', 'pid'].includes(key));
    if (keys.length == 7) valid++;
});

console.log(`In your batch file, how many passports are valid? ${valid} (${sw.elapsedMilliseconds()}ms)`);
