const fs = require('fs');
const { argv } = require('process');
const { Stopwatch } = require('../stopwatch');

const sw = new Stopwatch();
const inputs = fs.readFileSync(`${__dirname}/${argv[2] || 'example'}.txt`, { encoding:'utf8', flag:'r' }).trim().split("\n").map(line => parseInt(line, 10));

// Part 1
// First thought; sort the array and count all the differences. That way we are alway guaranteed of the minimal step from one adapter to the other. The works
// for both examples and for _my_ input. So, that is the solution we'll go with.

sw.start();
const sorted = JSON.parse(JSON.stringify(inputs));
sorted.sort((one, another) => one - another);
const device = sorted[sorted.length - 1] + 3;
sorted.push(device);

let voltage = 0;
const diffs = {1: 0, 3: 0}; // This will ensure these indices exist
for (let index = 0; index < sorted.length; index++) {
    const adapter = sorted[index];
    const diff = adapter - voltage;
    diffs[diff]++; // let it break with any other difference
    voltage = adapter;
}

const part_one = diffs[1] * diffs[3];
console.log(diffs[1], diffs[3], sorted.length);
console.log(`What is the number of 1-jolt differences multiplied by the number of 3-jolt differences? ${part_one} (${sw.elapsedMilliseconds()}ms)`);

// Part 2
// If we imagine the number of adapters as a tree structur where every node is an adapter. Then we can see that we potentially have three paths from every
// node/adapter. After ordering the adapters in ascending order we can iterate over all the adapters that we have starting a 0 volts. We can check if we 
// have an adapter for current + 1, current + 2 and current + 3. If we already have a number of paths for a destination, we add the number of paths of the
// current adapter to that number, otherwise we initiate to the number of paths of the current adapter. If we do this for every adapter then at the end all
// we have to do is fetch the number of paths towards the device, which is max(adapters) + 3.
const paths = {0: 1};
for (let index = -1; index < sorted.length; index++) {
    const voltage = sorted[index] || 0;
    const num_of_paths = paths[voltage] || 1;

    if (sorted.includes(voltage + 1)) {
        paths[voltage + 1] = (paths[voltage + 1] || 0) + num_of_paths;
    }
    if (sorted.includes(voltage + 2)) {
        paths[voltage + 2] = (paths[voltage + 2] || 0) + num_of_paths;
    }
    if (sorted.includes(voltage + 3)) {
        paths[voltage + 3] = (paths[voltage + 3] || 0) + num_of_paths;
    }
}
console.log(`What is the total number of distinct ways you can arrange the adapters to connect the charging outlet to your device? ${paths[device]} (${sw.elapsedMilliseconds()}ms)`);
