import { readFileSync } from 'fs';
import { Stopwatch } from '../stopwatch';

const input = readFileSync(`${__dirname}/${process.argv[2] || 'example'}.txt`, { encoding:'utf8', flag:'r' }).trim().split("\n").map(line => line.trim());
const debug: boolean = !!(process.env.DEBUG || false);

const forest = parse(input);

{
    using sw = new Stopwatch('part one');
    console.log('How many steps long is the longest hike?', part_one(forest));
}

{
    using sw = new Stopwatch('part two');
    console.log('How many steps long is the longest hike?', part_two(forest));
}


function part_one(forest: Forest): number {
    /**
     * Observations:
     * - we're not looking for the shortest path, but the longest path
     * - we cannot revisit a previous tile
     * - the majority of the traversals can only go one direction, aka a lot
     *   of corridors.
     *
     * This code uses a very optimistic DFS to solve this; I keep a list of
     * visited nodes in my state once I find a path to the goal I compare this
     * with previous attempts, and I take the max value. I have opted for DFS
     * over BFS because we have a lot of corridors. The difference was less than
     * 1 second.
     *
     * This approach runs in approx. 20 seconds. I think there is a better approach
     * possible; we could exclude the corridors and create a smaller graph. This
     * seems to be a max-flow problem[0], and there are algorithms to solve them.
     * For now I'm not implementing them, but maybe I'll need that for part 2...
     *
     * [0]: https://en.wikipedia.org/wiki/Maximum_flow_problem
     */

    type Point = {
        readonly row: number;
        readonly col: number;
    }

    type State = Point & {
        readonly visited: number[];
    }

    type Distance = {
        readonly distance: number;
        readonly previous?: Point;
    }

    const todo: State[] = [];
    const distances = {};

    const state_as_string = (state: State): string => JSON.stringify({ col: state.col, row: state.row, visited: state.visited });
    const id = ({ row, col }: Point): number => row * forest.width + col;

    const start = { row: 0, col: forest.trails[0].indexOf('.'), visited: [] };
    const end = { row: forest.height - 1, col: forest.trails[forest.height - 1].indexOf('.') };

    todo.push(start);
    distances[state_as_string(start)] = { distance: 0, previous: null };

    const add = (current: State, distance: number, delta_col: number, delta_row: number): void => {
        if (current.col + delta_col < 0 || current.col + delta_col >= forest.width || current.row + delta_row < 0 || current.row + delta_row >= forest.height) {
            return; // out of bounds
        }

        const trail = forest.trails[current.row + delta_row].charAt(current.col + delta_col);
        if (trail === '#') {
            return; //tree
        }

        if ((trail === '>' && delta_col !== 1)
            || (trail === '<' && delta_col !== -1)
            || (trail === 'v' && delta_row !== 1)
            || (trail === '^' && delta_row !== -1)) {
                return; // impossible slope
            }

        const new_point = { col: current.col + delta_col, row: current.row + delta_row };

        if (current.visited.includes(id(new_point))) {
            return; // we already visited this point
        }

        const projected = { ...new_point, visited: [...current.visited, id(new_point)] };

        if (state_as_string(projected) in distances === false || distances[state_as_string(projected)].distance > distance) {
            // Found a shorter path
            distances[state_as_string(projected)] = { distance: distance + 1, previous: null };
            todo.push(projected);
        }
    }

    let max_distance = 0;
    let max_distance_state = null;

    while (todo.length > 0) {
        const current = todo.pop();
        const { distance } = distances[state_as_string(current)];

        if (current.row === end.row && current.col === end.col) {
            if (distance > max_distance) {
                log('Found a (longer) path', distance);
                max_distance = distance;
                max_distance_state = current;
            }
            continue;
        }

        add(current, distance, 1, 0);
        add(current, distance, 0, 1);
        add(current, distance, -1, 0);
        add(current, distance, 0, -1);
    }

    // visualize
    if (process.argv.includes('--visualize')) {
        const path = max_distance_state.visited;
        const green = (str: string): string => `\x1b[42m${str}\x1b[0m`;
        for (let r = 0; r < forest.height; r++) {
            let line = '';
            for (let c = 0; c < forest.width; c++) {
                if (path.includes(id({ row: r, col: c }))) {
                    line += green(forest.trails[r].charAt(c));
                } else {
                    line += forest.trails[r].charAt(c);
                }
            }
            console.log(line);
        }
    }

    return max_distance;
}

function part_two({ width, height, trails }: Forest): number {
    /**
     * The same approach as part one did not work. In fact it runs out of memory.
     * First I convert the forest to a graph using the junctions as nodes. Using that I
     * first tried to perform DFS until I found the longest route, but that took
     * a long, long time. I found some advise on Reddit[0] that suggested using
     * recursion. That actually solves it relatively easy.
     *
     * I managed to bring down the speed a but further by taking out the start and
     * end "nodes" out of the recursion; there's only one path from start to the next
     * node, and the same goes for the end. So we take those out and add the distance
     * to the initial recursive call.
     *
     * [0]: https://www.reddit.com/r/adventofcode/comments/18oy4pc/comment/keto0pc/
     */

    type Point = {
        readonly col: number;
        readonly row: number;
    }

    type Vertex = {
        readonly start: string,
        readonly end: string,
        readonly distance: number;
    }

    // Find start and end, I take one step inwards. That way we are always within bounds
    // and that saves us some out of bounds checks.
    const start = { row: 1, col: trails[0].indexOf('.') };
    const end = { row: height - 2, col: trails[height - 1].indexOf('.') };

    const id = ({ row, col }: Point): number => row * width + col;

    const state_as_string = ({ row, col }: Point): string => `${col}x${row}`;

    const get = ({ row, col }: Point): string => trails[row]?.charAt(col) ?? '#';

    const is_junction = ({ row, col }: Point): boolean => {
        if (row <= 0 || row >= height - 1 || col <= 0 || col >= width - 1 || get({ row, col }) === '#') return false;

        let exits = 0;

        if (trails[row - 1].charAt(col) !== '#') exits++;
        if (trails[row + 1].charAt(col) !== '#') exits++;
        if (trails[row].charAt(col - 1) !== '#') exits++;
        if (trails[row].charAt(col + 1) !== '#') exits++;

        return exits > 2;
    }

    // Find all junctions
    const junctions: Point[] = [];
    for (let row = 0; row < height; row++) {
        //let line = '';
        const green = (str: string): string => `\x1b[42m${str}\x1b[0m`;

        for (let col = 0; col < width; col++) {
            if (is_junction({ row, col })) {
                junctions.push({ row, col });
                //line += green(get({ row, col }));
            } else {
                //line += get({ row, col });
            }
        }

        //console.log(line);
    }

    // Create a graph
    const graph: Record<string, Vertex[]> = {};

    // There is only 1 path from start to next node, and from last node to end as well.
    // We can take them out of our brute force:
    let first_after_start;
    let distance_to_first_after_start;
    let last_before_end;
    let distance_to_last_before_end;

    for (const { row, col } of junctions) {

        const equals = (one: Point, another: Point): boolean => one.row === another.row && one.col === another.col;

        const walk_until_junction = (from: Point, direction: Point): Vertex => {
            let previous = { ...from };
            let current = { row: from.row + direction.row, col: from.col + direction.col };
            let distance = 1;

            while (is_junction(current) === false && equals(current, start) === false && equals(current, end) === false) {
                if (!equals(previous, { row: current.row, col: current.col + 1 }) && get({ row: current.row, col: current.col + 1 }) !== '#') {
                    previous = current;
                    current = { row: current.row, col: current.col + 1 };
                    distance++;
                } else if (!equals(previous, { row: current.row + 1, col: current.col }) && get({ row: current.row + 1, col: current.col }) !== '#') {
                    previous = current;
                    current = { row: current.row + 1, col: current.col };
                    distance++;
                } else if (!equals(previous, { row: current.row, col: current.col - 1 }) && get({ row: current.row, col: current.col - 1 }) !== '#') {
                    previous = current;
                    current = { row: current.row, col: current.col - 1 };
                    distance++;
                } else if (!equals(previous, { row: current.row - 1, col: current.col }) && get({ row: current.row - 1, col: current.col }) !== '#') {
                    previous = current;
                    current = { row: current.row - 1, col: current.col };
                    distance++;
                } else {
                    console.log('THIS SHOULD NOT HAPPEN!', current, start);
                    process.exit(1);
                }
            }

            return {
                start: state_as_string(from),
                end: state_as_string(current),
                distance: distance + (equals(current, start) || equals(current, end) ? 1 : 0),
            };
        }

        for (const d of [{row: 0, col: 1}, {row: 1, col: 0}, {row: 0, col: -1}, {row: -1, col: 0}]) {
            if (get({ row: row + d.row, col: col + d.col}) !== '#') {
                // walk until next junction
                const vertex = walk_until_junction({ col, row }, d);

                if (vertex.end === state_as_string(start)) {
                    first_after_start = vertex.start;
                    distance_to_first_after_start = vertex.distance;
                } else if (vertex.end === state_as_string(end)) {
                    last_before_end = vertex.start;
                    distance_to_last_before_end = vertex.distance;
                } else {
                    graph[vertex.start] = [...(graph[vertex.start] ?? []), vertex];
                }
            }
        }
    }


    // a recursive approach
    const get_max = (graph: Record<string, Vertex[]>, start: string, end: string, visited: string[], distance: number): number => {
        if (start === end) {
            return distance;
        }

        visited = [ ...visited, start ];
        let max = 0;
        for (const vertex of graph[start]) {
            if (visited.includes(vertex.end)) {
                continue;
            }
            max = Math.max(max, get_max(graph, vertex.end, end, visited, distance + vertex.distance));
        }

        return max;
    }

    return get_max(graph, first_after_start, last_before_end, [], distance_to_first_after_start + distance_to_last_before_end);
}

type Forest = {
    readonly width: number;
    readonly height: number;
    readonly trails: string[];
}

function parse(input: string[]): Forest {
    return {
        height: input.length,
        width: input[0].length,
        trails: input,
    };
}

function log(...args: unknown[]): void {
    if (debug) {
        console.log(...args);
    }
}
