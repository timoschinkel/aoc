const fs = require('fs');
const { argv } = require('process');
const { Stopwatch } = require('../stopwatch');

const sw = new Stopwatch();
const inputs = fs.readFileSync(`${__dirname}/${argv[2] || 'example'}.txt`, { encoding:'utf8', flag:'r' }).trim().split("\n");

// Part 1
// Iterate over the instructions and apply them on the position of the ship. For this ship I keep track of the x and y position, and
// the facing of the ship.
sw.start();
const facings = [
    {dx: 0, dy: 1},     // N
    {dx: 1, dy: 0},     // E
    {dx: 0, dy: -1},    // S
    {dx: -1, dy: 0},    // W
];
const position = {x: 0, y: 0, facing: 1 /* east */ }

inputs.forEach(input => {
    const instruction = input[0];
    const value = parseInt(input.substring(1), 10);

    switch (instruction) {
        case 'N':
        case 'E':
        case 'S':
        case 'W':
            const directions = {
                N: {dx: 0, dy: 1},
                E: {dx: 1, dy: 0},
                S: {dx: 0, dy: -1},
                W: {dx: -1, dy: 0},
            };
            position.x = position.x + (directions[instruction].dx * value);
            position.y = position.y + (directions[instruction].dy * value);
            break;
        case 'L':
            position.facing = (position.facing + 4 - (value / 90)) % 4;
            break;
        case 'R':
            position.facing = (position.facing + (value / 90)) % 4;
            break;
        case 'F':
            const facing = facings[position.facing];
            position.x = position.x + (facing.dx * value);
            position.y = position.y + (facing.dy * value);
            break;
    }
});

const part_one = Math.abs(position.x) + Math.abs(position.y);
console.log(`What is the Manhattan distance between that location and the ship's starting position? ${part_one} (${sw.elapsedMilliseconds()}ms)`);

// Part 2
position.x = 0; position.y = 0, position.facing = 1; // reset
let waypoint = {x: 10, y: 1, facing: 1 /* east */ };

inputs.forEach(input => {
    const instruction = input[0];
    const value = parseInt(input.substring(1), 10);

    switch (instruction) {
        case 'N':
            waypoint.y += value;
            break;
        case 'E':
            waypoint.x += value;
            break;
        case 'S':
            waypoint.y -= value;
            break;
        case 'W':
            waypoint.x -= value;
            break;
        case 'L':
            for (let i = 0; i < value / 90; i++) {
                // rotate the waypoint around the ship left (counter-clockwise)
                waypoint = {x: waypoint.y * -1, y: waypoint.x};
            }
            break;
        case 'R':
            for (let i = 0; i < value / 90; i++) {
                // rotate the waypoint around the ship right (clockwise)
                waypoint = {x: waypoint.y, y: waypoint.x * -1};
            }
            break;
        case 'F':
            // move forward to the waypoint
            position.x = position.x + (waypoint.x * value);
            position.y = position.y + (waypoint.y * value);
            break;
    }
});

const part_two = Math.abs(position.x) + Math.abs(position.y);
console.log(`What is the Manhattan distance between that location and the ship's starting position? ${part_two} (${sw.elapsedMilliseconds()}ms)`);
