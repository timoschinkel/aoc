import { readFileSync } from 'fs';
import { Stopwatch } from '../stopwatch';

const input = readFileSync(`${__dirname}/${process.argv[2] || 'example'}.txt`, { encoding:'utf8', flag:'r' }).trim().split("\n\n").map(line => line.trim());
const debug: boolean = !!(process.env.DEBUG || false);

const i = parse(input);

{
    using sw = new Stopwatch('part one');
    console.log('What do you get if you add together all of the rating numbers for all of the parts that ultimately get accepted?', part_one(i));
}

{
    using sw = new Stopwatch('part two');
    console.log('How many distinct combinations of ratings will be accepted by the Elves\' workflows?', part_two(i));
}


function part_one(i: Input): number {
    /**
     * An exercise in keeping track of what you're trying to do; the biggest issue
     * is reading/parsing the puzzle input, and finding your way in your loops. I
     * could have set up some methods to declutter, but I opted for labeled loops.
     */

    let sum = 0;
    part_loop: for (const part of i.parts) {
        log('');
        log('Starting part', part);

        let workflow = i.workflows['in'];
        const path = ['in'];
        workflow_loop: while (workflow) {
            log('Testing against workflow');
            // go through the rules
            for(const rule of workflow.operations) {
                log('Testing for rule', rule);
                if ((rule.op === '>' && part[rule.prop] > rule.val)
                    || (rule.op === '<' && part[rule.prop] < rule.val)) {
                    log('Applying rule', rule);
                    if (['A', 'R'].includes(rule.target)) {
                        if (rule.target === 'A') {
                            log('Part is accepted');
                            sum += part.a + part.m + part.s + part.x;
                        } else {
                            log('Part is rejected');
                        }
                        path.push(rule.target);
                        log('part', part, 'path', path);
                        continue part_loop;
                    }
                    path.push(rule.target);
                    workflow = i.workflows[rule.target];
                    continue workflow_loop;
                }
            }

            log('No rule matched, falling back to', workflow.fallback);
            // none matched, we're going to the fallback
            if (['A', 'R'].includes(workflow.fallback)) {
                if (workflow.fallback === 'A') {
                    log('Part is accepted');
                    sum += part.a + part.m + part.s + part.x;
                } else {
                    log('Part is rejected');
                }
                path.push(workflow.fallback);
                log('part', part, 'path', path);
                continue part_loop;
            } else {
                path.push(workflow.fallback);
                workflow = i.workflows[workflow.fallback];
            }
        }
    }

    return sum;
}

function part_two(i: Input): number {
    /**
     * Hello day 5 :wave:
     *
     * Effectively the same approach as day 5; we want to determine the ranges
     * for which we either approach or reject. I start with a complete range
     * with every rating having a range from 1 to 4000 including. We start with
     * workflow 'in', and check the steps. For every step we split the range we
     * received in two; the "inside" that meets the operation, and the "outside"
     * that is left over. The next operation is applied to the "outside", until
     * there are no operations left and the "outside" that is left is sent to the
     * fallback. Perform this recursive until we have a range in R or A.
     */
    const start_ranges: Ranges = {
        a: { s: 1, e: 4000 },
        m: { s: 1, e: 4000 },
        s: { s: 1, e: 4000 },
        x: { s: 1, e: 4000 },
    }

    const count_combinations = (ranges: Ranges, wf: string, iterations: string[]): number => {
        if (wf === 'R') {
            return 0;
        }

        if (wf === 'A') {
            // count number of permutations
            log('ACCEPTED', 'iterations', iterations, ranges);
            return (ranges.a.s - ranges.a.e - 1) * (ranges.m.s - ranges.m.e - 1) * (ranges.s.s - ranges.s.e - 1) * (ranges.x.s - ranges.x.e - 1)
        }

        const split = (r: Range, o: Operation): Range[] => {
            if (o.op === '>') {
                return [
                    {
                        s: Math.max(r.s, o.val + 1),
                        e: r.e
                    },
                    // remainder:
                    {
                        s: r.s,
                        e: Math.min(r.e, o.val),
                    }
                ];
            } else { // <
                return [
                    {
                        s: r.s,
                        e: Math.min(r.e, o.val - 1),
                    },
                    // remainder:
                    {
                        s: Math.max(r.s, o.val),
                        e: r.e
                    }
                ];
            }
        }

        let remainder = { ...ranges };

        let combs = 0;

        const workflow = i.workflows[wf];
        for (const operation of workflow.operations) {
            // iterate over the operations, split up the range for each operation
            const [inside, outside] = split(remainder[operation.prop], operation);

            // Pass range to next workflow
            combs += count_combinations({
                a: operation.prop === 'a' ? inside : remainder.a,
                m: operation.prop === 'm' ? inside : remainder.m,
                s: operation.prop === 's' ? inside : remainder.s,
                x: operation.prop === 'x' ? inside : remainder.x,
            }, operation.target, [ ...iterations, wf ]);

            // Calculate remainder
            remainder = {
                a: operation.prop === 'a' ? outside : remainder.a,
                m: operation.prop === 'm' ? outside : remainder.m,
                s: operation.prop === 's' ? outside : remainder.s,
                x: operation.prop === 'x' ? outside : remainder.x,
            }
        }

        // take what remained and let it be handled by fallback:
        combs += count_combinations(remainder, workflow.fallback, [ ...iterations, wf ]);

        return combs;
    }

    return count_combinations(start_ranges, 'in', []);
}

type Ranges = {
    readonly a: Range;
    readonly m: Range;
    readonly s: Range;
    readonly x: Range;
}

type Range = {
    readonly s: number;
    readonly e: number;
}

type Operation = {
    readonly prop: string;
    readonly op: string;
    readonly val: number;
    readonly target: string;
}

type Workflow = {
    readonly operations: Operation[],
    readonly fallback: string;
}

type Part = {
    readonly x: number;
    readonly m: number;
    readonly a: number;
    readonly s: number;
}

type Input = {
    readonly workflows: Record<string, Workflow>;
    readonly parts: Part[];
}

function parse(input: string[]): Input {
    const workflows: Record<string, Workflow> = input[0].split("\n").map(row => row.trim()).reduce((carry, row) => {
        const [id, _, r] = row.split(/(\{|\})/s);
        const rules = r.split(',');

        return { ...carry, [id]: {
            fallback: rules[rules.length - 1],
            operations: r.split(',').slice(0, rules.length - 1).reduce((c, rule) => {
                const [p, val, target] = rule.split(/[><:]/);
                return [ ...c, {
                    prop: p, op: rule.includes('>') ? '>' : '<', val: parseInt(val), target
                }];
            }, [])
        } };
    }, {});

    const parts = input[1].split("\n").map(row => row.trim()).map(row => {
        const { groups } = row.match(/x=(?<x>[0-9]+),m=(?<m>[0-9]+),a=(?<a>[0-9]+),s=(?<s>[0-9]+)/si);
        return { x: parseInt(groups.x), m: parseInt(groups.m), a: parseInt(groups.a), s: parseInt(groups.s) }
    });

    return { workflows, parts };
}

function log(...args: unknown[]): void {
    if (debug) {
        console.log(...args);
    }
}
