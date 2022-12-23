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
        //
        // Part 2; I have seen some solutions that are generic for any input. I could not come up with a way to do this,
        // so I created a checkerboard view of the input - Print2() - and made an actual paper cube. I mapped all edges
        // and put them in my CanMove() method. Manual labor...  

        var separatorIndex = input.ToList().FindIndex(line => line == "");
        var inputGrid = input.Take(separatorIndex).ToArray();
        var inputCommands = input[separatorIndex + 1];

        int sideSize = EXAMPLE ? 4 : 50;
        int width = 0, height = inputGrid.Count();
        foreach (var line in inputGrid)
        {
            width = Math.Max(width, line.Length);
        }

        const int RIGHT = 0, DOWN = 1, LEFT = 2, UP = 3;
        Dictionary<(int x, int y), char> grid = new();
        int x = -1, y = -1;
        int orientation = RIGHT;
        
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

        // Create a copies to start with a clean slate for part 2
        var grid2 = grid.ToDictionary(entry => entry.Key, entry => entry.Value);
        int x2 = x, y2 = y;

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

        // Part 2
        grid = grid2;
        x = x2;
        y = y2;
        orientation = RIGHT;
        
        timer.Start();
        
        foreach (var command in commands)
        {
            ExecuteCommandPart2(command);
        }
        
        timer.Stop();
        
        score = (1000 * (y + 1)) + (4 * (x + 1)) + orientation;
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
        
        void ExecuteCommandPart2(string command)
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
            while (CanMovePart2(x, y, orientation, out var newPos, out var newOrientation) && steps > 0)
            {
                x = newPos.x;
                y = newPos.y;
                orientation = newOrientation;
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
        
        bool CanMovePart2(int startX, int startY, int orientation, out (int x, int y) newPosition, out int newOrientation)
        {
            var direction = orientations[orientation];
            
            newPosition = (x: (startX + direction.dx) % width, y: (startY + direction.dy) % height);
            newOrientation = orientation;
            
            // check "portals", these are hardcoded for MY input:
            //   A  B
            //   C
            // D E
            // F
            if (orientation == LEFT && startX == 50 && startY is >= 0 and < 50)
            { // walk off on the left side of A, and onto D
                // (50, 00) => (00, 149)
                // (50, 01) => (00, 148)
                // (50, 49) => (00, 100)
                newOrientation = RIGHT;
                direction = orientations[newOrientation];
                newPosition = (x: 00, y: 149 - startY);
            }
            else if (orientation == UP && startX is >= 50 and < 100 && startY == 0)
            { // walk off on the top side of A, and onto F
                // (50, 00) => (00, 150)
                // (51, 00) => (00, 151)
                // (99, 00) => (00, 199)
                newOrientation = RIGHT;
                direction = orientations[newOrientation];
                newPosition = (x: 0, y: 100 + startX);
            }
            else if (orientation == UP && startX is >= 100 and < 149 && startY == 0)
            { // walk off on the top side of B, and onto F
                // (100, 00) => (00, 200)
                // (101, 00) => (01, 200)
                // (149, 00) => (49, 200)
                newOrientation = UP;
                direction = orientations[newOrientation];
                newPosition = (x: startX - 100, y: 200);
            }
            else if (orientation == RIGHT && startX == 149 && startY is >= 0 and < 50)
            { // walk off on the right side of B, and onto E
                // (149, 00) => (99, 149)
                // (149, 01) => (99, 148)
                // (149, 49) => (99, 100)
                newOrientation = LEFT;
                direction = orientations[newOrientation];
                newPosition = (x: 99, y: 149 - startY);
            }
            else if (orientation == DOWN && startY == 49 && startX is >= 100 and < 150)
            { // walk off on the down side of B, and onto C
                // (100, 49) => (99, 50)
                // (101, 49) => (99, 51)
                // (149, 49) => (99, 99)
                newOrientation = LEFT;
                direction = orientations[newOrientation];
                newPosition = (x: 99, y: startX - 50);
            }
            else if (orientation == RIGHT && startX == 99 && startY is >= 50 and < 100)
            { // walk off on the right side of C, and onto B
                // (99, 50) => (100, 49)
                // (99, 51) => (101, 49)
                // (99, 99) => (149, 49) 
                newOrientation = UP;
                direction = orientations[newOrientation];
                newPosition = (x: startY + 50, y: 49);
            }
            else if (orientation == LEFT && startX == 50 && startY is >= 50 and < 100)
            { // walk off on the left side of C, and onto D
                // (50, 50) => (0, 100)
                // (50, 51) => (1, 100)
                // (50, 99) => (49, 100) 
                newOrientation = DOWN;
                direction = orientations[newOrientation];
                newPosition = (x: startY - 50, y: 100);
            }
            else if (orientation == UP && startY == 100 && startX is >= 0 and < 50)
            { // walk off on the top side of D, and onto C
                // (00, 100) => (50, 50)
                // (01, 100) => (50, 51)
                // (02, 100) => (50, 99) 
                newOrientation = RIGHT;
                direction = orientations[newOrientation];
                newPosition = (x: 50, y: startX + 50);
            }
            else if (orientation == LEFT && startX == 0 && startY is >= 100 and < 150)
            { // walk off on the left side of D, and onto A
                // (00, 100) => (50, 49)
                // (00, 101) => (50, 48)
                // (00, 149) => (50, 00) 
                newOrientation = RIGHT;
                direction = orientations[newOrientation];
                newPosition = (x: 50, y: 149 - startY);
            }
            else if (orientation == RIGHT && startX == 99 && startY is >= 100 and < 150)
            { // walk off on the right side of E, and onto B
                // (99, 100) => (149, 49)
                // (99, 101) => (149, 48)
                // (99, 149) => (149, 00) 
                newOrientation = LEFT;
                direction = orientations[newOrientation];
                newPosition = (x: 149, y: 149 - startY);
            }
            else if (orientation == DOWN && startY == 149 && startX is >= 50 and < 100)
            { // walk off on the down side of E, and onto F
                // (50, 149) => (49, 150)
                // (51, 149) => (49, 151)
                // (99, 149) => (49, 199) 
                newOrientation = LEFT;
                direction = orientations[newOrientation];
                newPosition = (x: 49, y: 100 + startX);
            }
            else if (orientation == LEFT && startX == 0 && startY is >= 150 and < 200)
            { // walk off on the left side of F, and onto A
                // (0, 150) => (50, 0)
                // (0, 151) => (51, 0)
                // (0, 199) => (99, 0) 
                newOrientation = DOWN;
                direction = orientations[newOrientation];
                newPosition = (x: startY - 100 , y: 0);
            }
            else if (orientation == RIGHT && startX == 49 && startY is >= 150 and < 200)
            { // walk off on the right side of F, and onto E
                // (49, 150) => (50, 149)
                // (49, 151) => (51, 149)
                // (49, 199) => (99, 149) 
                newOrientation = UP;
                direction = orientations[newOrientation];
                newPosition = (x: startY - 100 , y: 149);
            }
            else if (orientation == DOWN && startY == 199 && startX is >= 0 and < 50)
            { // walk off on the down side of F, and onto B
                // (00, 199) => (100, 0)
                // (01, 199) => (101, 0)
                // (49, 199) => (149, 0) 
                newOrientation = DOWN;
                direction = orientations[newOrientation];
                newPosition = (x: 100 + startX , y: 0);
            }
            
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
        
        void Print2()
        {
            for (var row = 0; row < height; row++)
            {
                for (var col = 0; col < width; col++)
                {
                    var c = grid.GetValueOrDefault((x: col, y: row), ' ');

                    if (((row / sideSize) + (col / sideSize)) % 2 == 0)
                    {
                        Console.ForegroundColor = ConsoleColor.Magenta;
                    }
                    else
                    {
                        Console.ResetColor();
                    }
                    Console.Write(c);
                }
                Console.WriteLine("");
            }
            Console.WriteLine("");
        }
    }
}