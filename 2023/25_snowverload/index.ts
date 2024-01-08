import { readFileSync } from 'fs';
import { Stopwatch } from '../stopwatch';
import { PriorityQueue } from '../PriorityQueue';

const input = readFileSync(`${__dirname}/${process.argv[2] || 'example'}.txt`, { encoding:'utf8', flag:'r' }).trim().split("\n").map(line => line.trim());
const debug: boolean = !!(process.env.DEBUG || false);

const graph = parse(input);

{
    using sw = new Stopwatch('part one');
    console.log('What do you get if you multiply the sizes of these two groups together?', part_one_karger(graph));
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
     *
     * Possible optimizations:
     * - Implement a min-cut algorithm
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

function part_one_karger(graph: Graph): number {
    /**
     * A more optimized approach is to use Karger's algorithm[0]. This algorithm
     * takes two random vertices and contracts/merges them. By running this algorithm
     * a number of time the probability of finding the minimum cut should be close
     * to 100%. In our scenario we already know that the minimum cut will be - 3 -,
     * so we can change that part by not running a maximum amount of times, but to
     * run it until we have a minimum cut cardinality of 3. By renaming the new
     * vertices {left},{right} are we able to calculate the number of vertices one
     * both sides of the minumum cut.
     *
     * This solution still is not very fast, but with a runtime of around 20 seconds
     * it is considerably facter than the initial solution, which ran in approx 5
     * minutes.
     *
     * [0]: https://en.wikipedia.org/wiki/Karger%27s_algorithm
     */

    // Convert into weighted graph
    const G = Object.entries(graph).reduce((carry, [node, edges]) => {
        return {
            ...carry,
            [node]: edges.map(destination => ({ destination, weight: 1 }))
        }
    }, {} as WeightedGraph);

    type MinCut = {
        readonly cardinality: number;
        readonly left: string;
        readonly right: string;
    }

    const karger = (g: WeightedGraph): MinCut => {
        const merge = (one: string, another: string): void => {
            /**    1                        1
             * A - - - B                A - - - B
             * |      /                 |
             * | 1  / 1                 | 1
             * |  /                     |
             * C                        C
             *
             * merging A & B
             *
             * A,B                     A,B
             *  |                       |
             *  | 2                     | 1
             *  |                       |
             *  C                       C
             *
             * Add the weights when both vertices are connected
             */
            const from_one = [ ...g[one] ];            // B, C
            const from_another = [ ...g[another] ];    // A, C

            const vertex = `${one},${another}`; // A,B
            const edges: Edge[] = [];

            for (const n of from_one) {
                if (n.destination === another) {
                    continue;
                }
                const also_in_another = from_another.find(edge => edge.destination === n.destination);
                if (also_in_another) {
                    edges.push({ destination: n.destination, weight: n.weight + also_in_another.weight });
                    g[n.destination] = g[n.destination].map(edge => edge.destination === one ? { destination: vertex, weight: edge.weight + also_in_another.weight } : edge);
                } else {
                    edges.push({ destination: n.destination, weight: n.weight });
                    g[n.destination] = g[n.destination].map(edge => edge.destination === one ? { destination: vertex, weight: edge.weight } : edge);
                }
            }
            delete g[one];

            for (const n of from_another) {
                if (n.destination === one) {
                    continue;
                }
                const also_in_one = from_one.find(edge => edge.destination === n.destination);
                if (also_in_one) {
                    // it is connected to both, but we already handled this previous loop
                    g[n.destination] = g[n.destination].filter(edge => edge.destination !== another);
                } else {
                    edges.push({ destination: n.destination, weight: n.weight });
                    g[n.destination] = g[n.destination].map(edge => edge.destination === another ? { destination: vertex, weight: edge.weight } : edge);
                }
            }
            delete g[another];

            g[vertex] = edges;
        }

        try {
            while (Object.keys(g).length > 2) {
                // find a random vertex and merge it with a random neighboring vertex
                const vertices = Object.keys(g);
                const vertex = vertices[Math.floor(Math.random() * vertices.length)];
                const neighbor = g[vertex][Math.floor(Math.random() * g[vertex].length)].destination;

                merge(vertex, neighbor);
            }
        } catch (err) {
            console.error(err);
            console.log(g);
        }

        return { cardinality: g[Object.keys(g)[0]][0].weight, left: Object.keys(g)[0], right: Object.keys(g)[1] };
    }

    let mincut = karger({ ...G });
    while (mincut.cardinality > 3) {
        mincut = karger({ ...G });
    }

    log('mincut', mincut, mincut.left.split(',').length, mincut.right.split(',').length, mincut.left.split(',').length * mincut.right.split(',').length);

    return mincut.left.split(',').length * mincut.right.split(',').length;
}

function part_two(): number {
    return 0;
}

type Edge = {
    weight: number;
    destination: string;
}

type WeightedGraph = {
    [key: string]: Edge[];
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
