const fs = require('fs');
const { argv } = require('process');
const { Stopwatch } = require('../stopwatch');

const sw = new Stopwatch();
const inputs = fs.readFileSync(`${__dirname}/${argv[2] || 'example'}.txt`, { encoding:'utf8', flag:'r' }).trim().split("\n");

// Read input
sw.start();

const bags = {};
const by_color = {};

inputs.forEach(line => {
    const [bag, contents] = line.split(/ bags contain /s);
    bags[bag] = {};
    if (contents != 'no other bags') {
        [...contents.matchAll(/(?<cnt>[0-9]+) (?<color>[a-z]+ [a-z]+) bags?/sg)].forEach(match => {
            // for part 2
            const { color, cnt } = match.groups;
            bags[bag][color] = parseInt(cnt, 10);
            // for part 1
            by_color[color] = (by_color[color] || []).concat([bag]);
        });
    }
});

console.log(`Done reading input (${sw.elapsedMilliseconds()}ms)`);

// Part 1
// Part 1 is about searching based on containing bags, aka the ingredients of the recipe. While reading the input the
// input is split up into two structures; One indexed by the holding bag - bags - and one indexed by the contained
// bag - by_color. To find the amount of bags can contain a shiny golden bag we create a stack - or queue, that does not
// really matter for this problem - that starts with all the bags that can hold a shiny golden bag. We iterate over this
// stack and find out all the bags that can contain the bag we are inspecting. If we find a bag that does not contain any
// other bags we don't put any new bags on the stack. We need to also keep track of the bags already visited.

sw.restart();
let part_one = [];
let todo = by_color['shiny gold'];
while(todo.length > 0) {
    const color = todo.pop();
    
    if (part_one.includes(color)) continue;

    part_one.push(color);
    todo = todo.concat(by_color[color] || []);
}
console.log(`How many bag colors can eventually contain at least one shiny gold bag? ${part_one.length} (${sw.elapsedMilliseconds()}ms)`);

// Part 2
// For part two I opted to use recursion with the `bags` index. We start at the shiny golden bag. For this bag we inspect what bags can go in 
// it. For every bag we call the same function - recursion - and multiply the answer with the amount. We add them up per bag to get the answer.
sw.restart();
const number_of_bags = (color) => {
    const contents = Object.entries(bags[color]);

    if (contents.length == 0) {
        return 0;
    }

    let num_of_bags = 0;
    contents.forEach(([bag, amount]) => {
        // For every bag inside `color` add the amount from the recipe and 
        // add the recursive number of bags multiplied by the amount.
        num_of_bags += amount;
        num_of_bags += number_of_bags(bag) * amount
    });

    return num_of_bags;
}
const part_two = number_of_bags('shiny gold');

console.log(`How many individual bags are required inside your single shiny gold bag? ${part_two} (${sw.elapsedMilliseconds()}ms)`);
