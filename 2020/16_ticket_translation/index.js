const fs = require('fs');
const { argv } = require('process');
const { Stopwatch } = require('../stopwatch');

const sw = new Stopwatch();
const inputs = fs.readFileSync(`${__dirname}/${argv[2] || 'example'}.txt`, { encoding:'utf8', flag:'r' }).trim().split('\n\n');

// read input
const rules = inputs[0].split('\n').reduce((carry, line) => {
    const matches = line.match(/^([^:]+): (\d+)-(\d+) or (\d+)-(\d+)$/);

    return {
        ...carry,
        [matches[1]]: {
            from_one: parseInt(matches[2], 10),
            to_one: parseInt(matches[3], 10),
            from_two: parseInt(matches[4], 10),
            to_two: parseInt(matches[5], 10),
        }
    }
}, {});

const my_ticket = inputs[1].split('\n')[1].split(',').map((i) => parseInt(i, 10));
const tickets = inputs[2].split('\n').slice(1).map((line) =>
    line.split(',').map((i) => parseInt(i, 10)));

const complies_with_rule = (value, rule) =>
    ((value >= rule.from_one && value <= rule.to_one)
        || (value >= rule.from_two && value <= rule.to_two));

const get_validity_score = (ticket) => {
    const complies_with_rules = (value) => {
        for (const rule of Object.values(rules)) {
            if (complies_with_rule(value, rule)) {
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
    return undefined;
}

const find_completely_invalid_tickets = () => {
    return tickets.reduce((carry, item) => carry + (get_validity_score(item) ?? 0), 0);
}

const find_product = () => {
    // Filter out invalid tickets - we can reuse some code of part 1 for that
    const valid = tickets.filter((ticket) => get_validity_score(ticket) === undefined);

    let eligible = new Array(valid[0].length).fill(Object.keys(rules));

    // iterate over the values
    for (let i = 0; i < eligible.length; i++) {
        // iterate over the tickets
        for (let r = 0; r < valid.length; r++) {
            const n = eligible[i].filter((rule) => complies_with_rule(valid[r][i], rules[rule]));

            if (n.length === 0) {
                throw new Error('Something went wrong!');
            }

            eligible[i] = n;
        }
    }

    // filter values; find a position with a single possible rule, eliminate this rule from
    // all other positions. Repeat until every position has a single option left over.

    const remove_from_list = (list, item) => {
        return list.filter((val) => val !== item);
    }

    const found = new Array(valid[0].length).fill(undefined);
    const filter = () => {
        for (let i = 0; i < eligible.length; i++) {
            if (found[i] === undefined && eligible[i].length === 1) {
                found[i] = eligible[i][0];
                eligible = eligible.map((rules) => remove_from_list(rules, eligible[i][0]));
                return true;
            }
        }

        return false;
    }

    while (filter()) {
        // repeat until all positions have a single possible rule
    }

    // We have all the rules, now find the product of all tickets that start with `departure`.
    let product = 1;
    for(let i = 0; i < found.length; i++) {
        if (found[i].startsWith('departure')) {
            product *= my_ticket[i];
        }
    }

    return product;
}

// Part 1
// Iterate over all the tickets and check every value against the rules. If any of the values does
// not match any rule, then the ticket is deemed invalid.
sw.start();
const part_one = find_completely_invalid_tickets();
console.log(`What is your ticket scanning error rate? ${part_one} (${sw.elapsedMilliseconds()}ms)`);

// Part 2
// I am using deduction here; per value we are going to check to which rules they can belong. We do
// this per "value index". So for the first value of every ticket we deduce to what rules it can comply.
// We do this also for the second value of every ticket, third value of every ticket etc.
//
// Because we need to exclude invalid tickets, we can reuse the code from part 1.
sw.start();
const part_two = find_product();
console.log(`What do you get if you multiply those six values together? ${part_two} (${sw.elapsedMilliseconds()}ms)`);
