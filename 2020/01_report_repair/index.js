const fs = require('fs');
const { argv } = require('process');
const { Stopwatch } = require('../stopwatch');

const sw = new Stopwatch();
const input = fs.readFileSync(`${__dirname}/${argv[2] || 'example'}.txt`, { encoding:'utf8', flag:'r' }).trim().split("\n").map(i => parseInt(i, 10));

// Part 1
sw.start();
for(let i = 0; i < input.length; i++) {
    const f = input.indexOf(2020 - input[i], i);
    if (f >= 0) {
        console.log(`Find the two entries that sum to 2020; what do you get if you multiply them together? ${input[i] * input[f]} (${sw.elapsedMilliseconds()}ms)`);
        break;
    }
}

// Part 2
sw.restart();
for(let first = 0; first < input.length; first++) {
    const target = 2020 - input[first];
    for (let second = first + 1; second < input.length; second++) {
        const match = input.indexOf(target - input[second]);
        if (match != -1) {
            console.log(`What is the product of the three entries that sum to 2020? ${input[first] * input[second] * input[match]} (${sw.elapsedMilliseconds()}ms)`);
            // artifical break 2
            first = second = input.length;
        }
    }
}
