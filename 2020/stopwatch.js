class Stopwatch {
    constructor() {
        this._start = null;
        this._stop = null;
    }

    start() {
        this._start = new Date();
    }

    restart() {
        this.start();
        this._stop = null;
    }

    stop() {
        this._stop = new Date();
    }

    elapsedMilliseconds () {
        const begin = this._start || new Date();
        const end = this._stop || new Date();

        return end.getTime() - begin.getTime();
    }
}

module.exports = { Stopwatch };
