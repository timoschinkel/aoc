const fs = require('fs');
const { argv } = require('process');
const { Stopwatch } = require('../stopwatch');

const sw = new Stopwatch();
const inputs = fs.readFileSync(`${__dirname}/${argv[2] || 'example'}.txt`, { encoding:'utf8', flag:'r' }).trim().split('\n\n');

// read input
const rules = inputs[0].split('\n').map((line) => {
   const matches = line.match(/^([^:]+): (\d+)-(\d+) or (\d+)-(\d+)$/);

   return {
       name: matches[1],
       from_one: parseInt(matches[2], 10),
       to_one: parseInt(matches[3], 10),
       from_two: parseInt(matches[4], 10),
       to_two: parseInt(matches[5], 10),
   }
});

const my_ticket = [];
const tickets = inputs[2].split('\n').slice(1).map((line) =>
    line.split(',').map((i) => parseInt(i, 10)));

const get_validity_score = (ticket) => {
    const complies_with_rules = (value) => {
        for (const rule of rules) {
            if ((value >= rule.from_one && value <= rule.to_one)
                || (value >= rule.from_two && value <= rule.to_two)) {
                return true;
            }
        }

        return false;
    }

    for (const value of ticket) {
        if (!complies_with_rules(value)) {
            //console.log(`Value ${value} does not comply with any rule`);
            return value;
        }
    }

    // ticket is valid, score is 0
    return 0;
}

const find_completely_invalid_tickets = () => {
    return tickets.reduce((carry, item) => carry + get_validity_score(item), 0);
}

// Part 1
// Iterate over all the tickets and check every value against the rules. If any of the values does
// not match any rule, then the ticket is deemed invalid.
sw.start();
const part_one = find_completely_invalid_tickets();
console.log(`What is your ticket scanning error rate? ${part_one} (${sw.elapsedMilliseconds()}ms)`);

