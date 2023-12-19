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
    console.log('What are the new total winnings?', part_two());
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

function part_two(): number {
    return 0;
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
