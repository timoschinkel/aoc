export class Stopwatch {
    private readonly start: DOMHighResTimeStamp;
    constructor(
        private readonly id: string,
    ) {
        this.start = performance.now();
    }

    private static human_readable(duration: number) {
        return `${Math.round(duration * 1000) / 1000}ms`
    }

    [Symbol.dispose] () {
        const duration = performance.now() - this.start;
        const human_readable = Stopwatch.human_readable(duration);

        console.info(`Duration of ${this.id}: ${human_readable}`);
    }
}
