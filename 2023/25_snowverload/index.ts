import { readFileSync } from 'fs';
import { Stopwatch } from '../stopwatch';
import { PriorityQueue } from '../PriorityQueue';

const input = readFileSync(`${__dirname}/${process.argv[2] || 'example'}.txt`, { encoding:'utf8', flag:'r' }).trim().split("\n").map(line => line.trim());
const debug: boolean = !!(process.env.DEBUG || false);

const graph = parse(input);

{
    using sw = new Stopwatch('part one');
    console.log('What do you get if you multiply the sizes of these two groups together?', part_one(graph));
}

{
    using sw = new Stopwatch('part two');
    console.log('What are the new total winnings?', part_two());
}


function part_one(graph: Graph): number {
    /**
     * This is a min-cut problem. But I don't have the time right now to implement
     * an algorithm like Stoer-Wagner or Ford-Fulkerson. So I'll brute force for now.
     *
     * Idea is to find the shortest paths for every combination of nodes, and
     * find out what paths we traverse the most. Those should probably be the
     * paths the be cut. We remove these edges from the graph and count the number of
     * reachable nodes for each side.
     */
    type Distance<T> = {
        readonly distance: number;
        readonly previous?: T;
    }

    const dijkstra = (graph: Graph, start: string, goal: string): string[] => {
        const queue = new PriorityQueue<string>();
        const distances: Record<string, Distance<string>> = {};

        // add first
        queue.push(0, start);
        distances[start] = { distance: 0, previous: null };

        while (queue.length > 0) {
            const { priority: distance, value: current } = queue.pop();

            if (current === goal) {
                break; // found shortest path
            }

            for (const next of graph[current]) {
                if (next in distances) continue;

                distances[next] = { distance: distance + 1, previous: current };
                queue.push(distance + 1, next);
            }
        }

        const paths = [];
        let c = goal;
        while (c !== null) {
            const shortest = distances[c];
            if (shortest.previous) {
                const path = [shortest.previous, c];
                path.sort();
                paths.push(path.join('-'));
            }

            c = shortest.previous;
        }

        return paths;
    }

    const nodes = Object.keys(graph);
    const paths: Record<string, number> = {};
    for (let s = 0; s < nodes.length - 1; s++) {
        for (let g = s + 1; g < nodes.length; g++) {
            const shortest_path = dijkstra(graph, nodes[s], nodes[g]);
            for (const path of shortest_path) {
                paths[path] = (paths[path] ?? 0) + 1;
            }
        }
    }

    // Find 3 busiest paths
    const sorted = Object.entries(paths);
    sorted.sort((one, another) => another[1] - one[1]);

    const to_remove = sorted.slice(0, 3);

    // remove these edges and count the number of nodes reachable
    for (const edge of to_remove) {
        const [from, to] = edge[0].split('-');

        graph[from] = graph[from].filter(node => node !== to);
        graph[to] = graph[to].filter(node => node !== from);
    }

    const [one, another] = to_remove[0][0].split('-');

    const count_reachable = (graph: Graph, start: string): number => {
        const visited: string[] = [start];
        const to_check = [start];

        while (to_check.length > 0) {
            const current = to_check.shift();
            for (const next of graph[current]) {
                if (!visited.includes(next)) {
                    visited.push(next);
                    to_check.push(next);
                }
            }
        }

        return visited.length;
    }

    // count from one and count from another
    log('one', one, count_reachable(graph, one), 'another', another, count_reachable(graph, another));

    return count_reachable(graph, one) * count_reachable(graph, another);
}

function part_two(): number {
    return 0;
}

type Graph = {
    [key: string]: string[];
}

function parse(input: string[]): Graph {
    return input.reduce((carry, line) => {

        const [l, r] = line.split(': ');
        const nodes = r.split(' ');

        carry = {
            ...carry,
            [l]: [...(carry[l] || []), ...nodes]
        }

        for (const node of nodes) {
            carry = {
                ...carry,
                [node]: [...(carry[node] || []), l]
            }
        }

        return carry;
    }, {});
}

function log(...args: unknown[]): void {
    if (debug) {
        console.log(...args);
    }
}
