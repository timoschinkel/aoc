using System.Diagnostics;
using System.Reflection;

namespace Day24;

class Program
{
    public static void Main(string[] args)
    {
        bool DEBUG = args.Contains("debug");
        bool EXAMPLE = args.Contains("example");

        string[] input = EXAMPLE
            ? File.ReadAllLines($"{Path.GetDirectoryName(Assembly.GetExecutingAssembly().Location)}/example.txt")
            : File.ReadAllLines($"{Path.GetDirectoryName(Assembly.GetExecutingAssembly().Location)}/input.txt");

        var timer = new Stopwatch();
        
        // Part 1; I needed some help with this one. Majority of Reddit said "just use BFS", but I realised my knowledge
        // of BFS was not sufficient. After looking at some more in depth explanations I was able to wrap my head around
        // the idea that I don't need to keep a list of best scores for every position. The characteristic of BFS is
        // that once we reach the end point, we know that we have done so in the minimum required number of steps.
        
        // read input
        int width = input[0].Length, height = input.Length;
        var grid = new char[width, height];
        List<Blizzard> blizzards = new();
        
        (int x, int y) start = (x: 1, y: 0), end = (x: width - 2, y: height - 1);
        
        for (int row = 0; row < input.Length; row++)
        {
            for (int column = 0; column < input[row].Length; column++)
            {
                if (row == 0 && input[row][column] == '.') start = (x: column, y: row);
                if (row == height - 1 && input[row][column] == '.') end = (x: column, y: row);

                switch (input[row][column])
                {
                    case '#':
                    case '.':
                        grid[column, row] = input[row][column];
                        break;
                    case '^':
                        blizzards.Add((new Blizzard{ Column = column, Row = row, Direction = (x: 0, y: -1) }));
                        break;
                    case '>':
                        blizzards.Add((new Blizzard{ Column = column, Row = row, Direction = (x: +1, y: 0) }));
                        break;
                    case 'v':
                        blizzards.Add((new Blizzard{ Column = column, Row = row, Direction = (x: 0, y: +1) }));
                        break;
                    case '<':
                        blizzards.Add((new Blizzard{ Column = column, Row = row, Direction = (x: -1, y: 0) }));
                        break;
                    default:
                        throw new Exception("Impossible!");
                }
            }
        }
        
        timer.Start();
            
        // Calculate all possible blizzard patterns. As our grid has height - 2 (2 walls) available spots vertically
        // and width - 2 available spots horizontally the amount of possible states is the Least Common Multiple -
        // LCM. We can also calculate these at request time, but I think we will visit all possible states, so pre-
        // calculating seems a logical choice.

        var lcm = LCM(width - 2, height - 2);
        if (DEBUG) Console.WriteLine($"Width: {width}, Height: {height}, LCM: {lcm}");
        string?[,,] states = new string[lcm, width, height]; // time, row, column

        for (int t = 0; t < lcm; t++)
        {
            foreach (var blizzard in blizzards)
            {
                if (t > 0)
                {
                    // calculate new position
                    blizzard.Row += blizzard.Direction.y;
                    blizzard.Column += blizzard.Direction.x;

                    // Check boundaries
                    if (blizzard.Column == 0) blizzard.Column = width - 2;
                    if (blizzard.Column == width - 1) blizzard.Column = 1;
                    if (blizzard.Row == 0) blizzard.Row = height - 2;
                    if (blizzard.Row == height - 1) blizzard.Row = 1;
                }

                states[t, blizzard.Column, blizzard.Row] += blizzard.Character;
            }
            PrintAt(t);
        }
        
        // Perform a Breath First Search over three dimensions; t, column and row. Every step we have five possible 
        // operations; move up, move right, move down, move left, or wait. We will continue performing this until the 
        // queue is empty or when we reach end.
        Queue<(int t, int x, int y)> queue = new Queue<(int t, int x, int y)>();
        Dictionary<(int t, int x, int y), bool> visited = new();
        int steps = 0;

        queue.Enqueue((t: 0, x: start.x, y: start.y));
        while (queue.Count > 0)
        {
            var current = queue.Dequeue();
            if (visited.ContainsKey(current))
            {
                // We have already visited this state, so we don't need to visit it again
                continue;
            }

            visited[current] = true;
            
            // Check all possible directions
            var actions = new List<(int x, int y)>
            {
                (x: 0, y: -1), // up
                (x: +1, y: 0), // right
                (x: 0, y: +1), // down
                (x: -1, y: 0), // left
                (x: 0, y: 0), // wait
            };

            // Check if we have reached the end. If so - because of BFS characteristics - we know that we have found the
            // the shortest path from start to end.
            if (current.x == end.x && current.y == end.y)
            {
                steps = current.t;
                break;
            }
            
            foreach (var action in actions)
            {
                var move = (t: current.t + 1, x: current.x + action.x, y: current.y + action.y);
                if (CanMove(move.t, move.x, move.y))
                {
                    queue.Enqueue(move);
                }
            }
        }
        
        timer.Stop();
        Console.WriteLine($"What is the fewest number of minutes required to avoid the blizzards and reach the goal? {steps} ({timer.ElapsedMilliseconds}ms)");

        bool CanMove(int t, int x, int y)
        {
            if (x < 0 || x > width - 1 || y < 0 || y > height - 1 || grid[x, y] == '#') return false; // out of bounds

            var state = t % lcm;
            return states[state, x, y] == null;
        }
        
        int LCM(int a, int b)
        {
            // https://stackoverflow.com/a/13569863/2118802
            int num1, num2;
            if (a > b)
            {
                num1 = a; num2 = b;
            }
            else
            {
                num1 = b; num2 = a;
            }

            for (int i = 1; i < num2; i++)
            {
                int mult = num1 * i;
                if (mult % num2 == 0)
                {
                    return mult;
                }
            }
            return num1 * num2;
        }

        void PrintAt(int t)
        {
            if (!DEBUG) return;

            Console.WriteLine($"== Blizzards at minute {t} ==");
            for (int row = 0; row < height; row++)
            {
                for (int column = 0; column < width; column++)
                {
                    if (grid[column, row] == '#')
                    {
                        Console.Write('#');
                        continue;
                    }

                    var bs = states[t, column, row];
                    if (bs == null)
                    {
                        Console.Write('.');
                    }
                    else if (bs.Length > 1)
                    {
                        Console.Write(bs.Length);
                    }
                    else
                    {
                        Console.Write(bs);
                    }
                }
                Console.WriteLine("");
            }
            Console.WriteLine("");
        }
    }

    private class Blizzard
    {
        public int Row { get; set; }
        public int Column { get; set; }
        public (int x, int y) Direction;

        public char Character
        {
            get
            {
                switch (Direction)
                {
                    case (x: 0, y: -1): return '^';
                    case (x: -1, y: 0): return '<';
                    case (x: 0, y: +1): return 'v';
                    case (x: +1, y: 0): return '>';
                    default: return '?';
                }  
            }
        }
    }
}