using System.Diagnostics;
using System.Reflection;
using System.Text.RegularExpressions;

namespace Day22;

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
        
        // Part 1; Read the input into a dictionary, and simply perform every command step by step. Pitfalls I fell into
        // where applying the direction vector (int dx, int dy) on the x and y position, and performing a modulo to 
        // implement the wrap around, but forgetting to take walking left/up. Easy solution is to always add width or
        // height before applying the directional vector.

        var separatorIndex = input.ToList().FindIndex(line => line == "");
        var inputGrid = input.Take(separatorIndex).ToArray();
        var inputCommands = input[separatorIndex + 1];

        int width = 0, height = inputGrid.Count();
        foreach (var line in inputGrid)
        {
            width = Math.Max(width, line.Length);
        }

        Dictionary<(int x, int y), char> grid = new();
        int x = -1, y = -1;
        int orientation = 0; // right
        
        var orientations = new []
        {
            (dx: 1, dy: 0), // right = 0
            (dx: 0, dy: 1), // down = 1
            (dx: -1, dy: 0), // left = 2
            (dx: 0, dy: -1), // up = 3
        };
        var orientationNotations = new[] { '>', 'V', '<', '^' };
        
        for (var row = 0; row < inputGrid.Count(); row++)
        {
            for (var col = 0; col < inputGrid[row].Length; col++)
            {
                char c = inputGrid[row][col];
                if (c == ' ') continue; // don't need that in the grid
                if (c == '.' && x == -1 && y == -1)
                {
                    x = col;
                    y = row;
                    c = orientationNotations[orientation];
                }

                grid[(x: col, y: row)] = c;
            }
        }

        if (DEBUG) Console.WriteLine($"grid: {width} x {height}");
        if (DEBUG) Console.WriteLine($"Orientation: {orientation}");
        if (DEBUG) Console.WriteLine($"Position: ({x}, {y})");

        Print();
        
        timer.Start();
        
        var commands = Regex.Matches(inputCommands, @"([RL]|\d+)").Select(match => match.Value).ToArray();
        if (DEBUG) Console.WriteLine($"Executing {commands.Count()} commands...");

        foreach (var command in commands)
        {
            // execute command!
            ExecuteCommand(command);
        }
        
        if (DEBUG) Console.WriteLine($"Final position: ({x}, {y})");
        if (DEBUG) Console.WriteLine($"Final orientation: {orientationNotations[orientation]} == {orientation}");

        var score = (1000 * (y + 1)) + (4 * (x + 1)) + orientation;
        timer.Stop();
        
        Console.WriteLine($"What is the final password? {score} ({timer.ElapsedMilliseconds}ms)");

        void ExecuteCommand(string command)
        {
            if (command == "R")
            {
                // rotate clockwise
                if (DEBUG) Console.WriteLine($"Rotate clockwise at ({x}, {y})");
                orientation = (orientation + 1) % orientations.Length;
                grid[(x, y)] = orientationNotations[orientation];
                return;
            }

            if (command == "L")
            {
                // rotate counter clockwise
                if (DEBUG) Console.WriteLine($"Rotate counter clockwise at ({x}, {y})");
                orientation = (orientation + orientations.Length - 1) % orientations.Length;
                grid[(x, y)] = orientationNotations[orientation];
                return;
            }
            
            // we need to walk
            var steps = int.Parse(command);
            if (DEBUG) Console.WriteLine($"Walk {steps} steps starting at ({x}, {y})");
            while (CanMove(x, y, orientations[orientation], out var newPos) && steps > 0)
            {
                x = newPos.x;
                y = newPos.y;
                grid[(x, y)] = orientationNotations[orientation];
                steps--;
            }

            Print();
        }
        
        bool CanMove(int startX, int startY, (int dx, int dy) direction, out (int x, int y) newPosition)
        {
            newPosition = (x: (startX + direction.dx) % width, y: (startY + direction.dy) % height);
            while (grid.ContainsKey(newPosition) == false)
            {
                newPosition = (x: (newPosition.x + direction.dx + width) % width, y: (newPosition.y + direction.dy + height) % height);
            }
            
            return grid[newPosition] != '#';
        }

        void Print()
        {
            if (!DEBUG) return;

            for (var row = 0; row < height; row++)
            {
                for (var col = 0; col < width; col++)
                {
                    var c = grid.GetValueOrDefault((x: col, y: row), ' ');
                    if (row == y && col == x)
                    {
                        Console.ForegroundColor = ConsoleColor.Magenta;
                    }
                    else if(c == '#')
                    {
                        Console.ForegroundColor = ConsoleColor.DarkYellow;
                    }
                    else if (c != '.')
                    {
                        Console.ForegroundColor = ConsoleColor.Cyan;
                    }
                    Console.Write(c);
                    Console.ResetColor();
                }
                Console.WriteLine("");
            }
            Console.WriteLine("");
        }
    }
}