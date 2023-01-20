const fs = require('fs');
const { argv } = require('process');
const { Stopwatch } = require('../stopwatch');

const sw = new Stopwatch();
const input = fs.readFileSync(`${__dirname}/${argv[2] || 'example'}.txt`, { encoding:'utf8', flag:'r' }).trim().split("\n");

// Part 1
// We can benefit from the fact that JavaScript allows us to use a string as if it is an array in most situations. 
// We keep a tree counter and perform the movement pattern of the Toboggan until we pass the bottom of the input. Only thing to
// consider is that the input is only a section that horizontally repeats into infinity. This is remedied by performing a modulo
// of the x position and the width of the input.
sw.start();
let x = 3, y = 1, trees = 0, width = input[0].length;
while (y < input.length) {
    if (input[y][x] == '#') trees++;
    x = (x + 3) % width;
    y += 1;
}

console.log(`How many trees would you encounter? ${trees} (${sw.elapsedMilliseconds()}ms)`);

// Part 2
// A repetition of part 1, but now with an array of instructions dx and dy. Using the characteristic that the order of numbers
// when calculating the product is irrelevant I start with the value of part 1, calculate the remaining values and multiply
// them.
sw.restart();
[{dx: 1, dy: 1}, {dx: 5, dy: 1}, {dx: 7, dy: 1}, {dx: 1, dy: 2}].forEach(({dx, dy}) => {
    let t = 0;
    x = dx, y = dy;
 
    while (y < input.length) {
        if (input[y][x] == '#') t++;
        x = (x + dx) % width;
        y += dy;
    }

    trees *= t;
});

console.log(`What do you get if you multiply together the number of trees encountered on each of the listed slopes? ${trees} (${sw.elapsedMilliseconds()}ms)`);
