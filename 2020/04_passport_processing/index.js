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

// Part 2
// Not that much different compared to part 1; Besides checking the presence of the keys, I also check the validity. By passing this
// through Array.filter() and returning false if the key is present, but with an invalid value, I can check the validity of the entire
// entry by checking the number of valid pairs to 7.
sw.restart();
valid = 0;
input.forEach(entry => {
    const keys = entry
        .split(/\s+/)
        .map(pair => pair.split(':'))
        .filter(([key, value]) => {
            switch(key) {
                case 'byr':
                    const byr = parseInt(value, 10);
                    return byr >= 1920 && byr <= 2002;
                case 'iyr':
                    const iyr = parseInt(value, 10);
                    return iyr >= 2010 && iyr <= 2020;
                case 'eyr':
                    const eyr = parseInt(value, 10);
                    return eyr >= 2020 && eyr <= 2030
                case 'hgt':
                    const match = value.match(/^(?<hgt>[0-9]+)(?<unit>in|cm)$/s);
                    if (match == null) return false;
                    const hgt = parseInt(match.groups.hgt, 10);
                    return match.groups.unit == 'in'
                        ? (hgt >= 59 && hgt <= 76)
                        : (hgt >= 150 && hgt <= 193);
                case 'hcl':
                    return value.match(/^#[0-9a-f]{6}$/s) != null;
                case 'ecl':
                    return ['amb', 'blu', 'brn', 'gry', 'grn', 'hzl', 'oth'].includes(value);
                case 'pid':
                    return value.match(/^[0-9]{9}$/s) != null;
                default:
                    return false;
            }
        });
    if (keys.length == 7) valid++;
});

console.log(`In your batch file, how many passports are valid? ${valid} (${sw.elapsedMilliseconds()}ms)`);
