import { readFileSync } from 'fs';

const input = readFileSync(`${__dirname}/${process.argv[2] || 'example'}.txt`, { encoding:'utf8', flag:'r' }).trim().split("\n");
const debug: boolean = false;

console.log('What is the sum of all of the calibration values?', part_one(input));
console.log('What is the sum of all of the calibration values?', part_two(input));

function part_one(input: string[]): number {
    let sum = 0;

    input.forEach(line => {
        const list = line.split('').filter(char => char === parseInt(char, 10).toString());
        const calibration_value = parseInt(`${list[0]}${list[list.length - 1]}`, 10);
        log(line, list, calibration_value);
        sum += calibration_value;
    });

    return sum;
}

function part_two(input: string[]): number {
    // The difficulty here is that eigh2two3three should be converted to 82233,
    // so the textual representation "eight" can be split by multiple numeric chars.
    // Let's do regular expressions!

    const converted = input.map(line => {
        const c = Object.entries({
            '(z[0-9]*e[0-9]*r[0-9]*o)': '0$1',
            '(o[0-9]*n[0-9]*e)': '1$1',
            '(t[0-9]*w[0-9]*o)': '2$1',
            '(t[0-9]*h[0-9]*r[0-9]*e[0-9]*e)': '3$1',
            '(f[0-9]*o[0-9]*u[0-9]*r)': '4$1',
            '(f[0-9]*i[0-9]*v[0-9]*e)': '5$1',
            '(s[0-9]*i[0-9]*x)': '6$1',
            '(s[0-9]*e[0-9]*v[0-9]*e[0-9]*n)': '7$1',
            '(e[0-9]*i[0-9]*g[0-9]*h[0-9]*t)': '8$1',
            '(n[0-9]*i[0-9]*n[0-9]*e)': '9$1',
        }).reduce((carry: string, [search, replace]) => {
            return carry.replaceAll(new RegExp(search, 'gsi'), replace);
        }, line);
        log(line, c);
        return c;
    });

    return part_one(converted);
}

function log(...args: unknown[]): void {
    if (debug) {
        console.log(...args);
    }
}
