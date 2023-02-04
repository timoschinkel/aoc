const fs = require('fs');
const { argv } = require('process');
const { Stopwatch } = require('../stopwatch');

const sw = new Stopwatch();
const inputs = fs.readFileSync(`${__dirname}/${argv[2] || 'example'}.txt`, { encoding:'utf8', flag:'r' }).trim().split("\n");

String.prototype.reverse = function() {
    let r = '';
    for(let i = 0; i < this.length; i++) {
        r = this[i] + r;
    }
    return r;
}

// Part 1
// I have been looking at a combination of bitwise operators to make this work, but I could not find it. So I am applying the masking
// based on string values. I imagine that this is slower, but it yields the correct result.
// Apply the instructions one by one; set mask and apply mask.

sw.start();
let registers = new Array(36).fill(0);

/**
 * @param {Number} value 
 * @param {String} mask 
 * @returns 
 */
const apply_mask = (value, mask) => {
    const length = mask.length;
    const str = value.toString(2).reverse();

    let masked = '';
    for (let i = 0; i < length; i++) {
        const digit = mask[length - 1 - i];
        if (digit == 'X') {
            masked = (str[i] || '0') + masked;
            continue;
        }
        
        if (digit == '0') {
            masked = '0' + masked;
            continue;
        }

        if (digit == '1') {
            masked = '1' + masked;
            continue;
        }
    }
    return parseInt(masked, 2);
}

let mask = 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX';
inputs.forEach(input => {
    if (input.startsWith('mask = ')) {
        mask = input.replace('mask = ', '');
    } else {
        const match = input.match(/mem\[(?<register>\d+)\] = (?<value>\d+)/s);
        const register = match.groups.register;
        const value = parseInt(match.groups.value, 10);

        registers[register] = apply_mask(value, mask);
    }
});

const part_one = registers.filter(r => r > 0).reduce((c, v) => v + c, 0);
console.log(`What is the sum of all values left in memory after it completes? ${part_one} (${sw.elapsedMilliseconds()}ms)`);

// Part 2
// Two big challenges for me; Understanding the puzzle and finding all permutations of the addresses to write the value to.
// The latter is accomplished by keeping track of all X positions when applying the mask. To find all addresses replace all
// X values in the masked address with 0, put that address in a list, iterate over all X positions and per X position iterate
// over all addresses in our list. Replace the 0 on the X position with 1 in all addresses and add those to the list so the 
// next X position will replace their X position for all existing strings.
// I managed to exceed the limits of Arrays within JavaScript as the answer to part 2 came to 0. Switching from Array of 
// dictionary solved this issue.

String.prototype.leftPad = function(length, char) {
    let str = this;
    while (str.length < length) {
        str = char + str;
    }
    return str;
}

const apply_mask_part_two = (decimal, mask) => {
    const str = decimal.toString(2).leftPad(36, '0');

    let masked = '';
    let x_positions = [];

    for (let i = 35; i >= 0; i--) {
        if (mask[i] == 'X') {
            masked = 'X' + masked;
            x_positions.push(i);
        }
        if (mask[i] == '0') masked = str[i] + masked;
        if (mask[i] == '1') masked = '1' + masked;
    }

    return {
        masked,
        x_positions
    };
};

const find_all_addresses = ({ masked, x_positions }) => {
    let addresses = [masked.replaceAll('X', '0')]; // replace all x with 0

    x_positions.forEach(x_position => {
        let new_addresses = [];
        addresses.forEach(address => {
            new_addresses.push(address.substring(0, x_position) + '1' + address.substring(x_position + 1));
        });
        addresses = addresses.concat(new_addresses);
    });

    return addresses.map(address => parseInt(address, 2));
};

mask = '000000000000000000000000000000000000';
registers = {};
inputs.forEach(input => {
    if (input.startsWith('mask = ')) {
        mask = input.replace('mask = ', '');
    } else {
        const match = input.match(/mem\[(?<register>\d+)\] = (?<value>\d+)/s);
        const register = parseInt(match.groups.register, 10);
        const value = parseInt(match.groups.value, 10);

        const masked = apply_mask_part_two(register, mask);
        
        // find all addresses and write `value` to those addresses
        find_all_addresses(masked).forEach(address => {
            registers[address] = value;
        });
    }
});

const part_two = Object.values(registers).filter(r => r > 0).reduce((c, v) => v + c, 0);
console.log(`What is the sum of all values left in memory after it completes? ${part_two} (${sw.elapsedMilliseconds()}ms)`);
