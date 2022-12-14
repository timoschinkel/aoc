using System.Reflection;

namespace Day14;

class Program
{
    public static void Main(string[] args)
    {
        bool DEBUG = args.Contains("debug");
        bool ANIMATE = args.Contains("animate");

        char ROCK = '#';
        char AIR = '.';
        char SOURCE = '+';
        char SAND = 'o';

        string[] input =
            File.ReadAllLines($"{Path.GetDirectoryName(Assembly.GetExecutingAssembly().Location)}/input.txt");

        // Part 1; Not proud of how I have parsed the input data. Nor very proud on how I put the lines in the "cave".
        // What I did was iterate over all lines in the input, and in the first pass I keep track of the maximum and 
        // minimum for the x and y axis. After that I generate a two dimensional grid and draw all walls in there. That
        // grid is used to drop sand units, one by one, until it drops into the void. A similar approach is taken for 
        // the dropping of the individual sand unit; Look below, if empty, drop one step, if not empty check if left 
        // below is empty and repeat for right below.
        
        int minX = 500, maxX = 500, minY = 0, maxY = 10; // for drawing purposes

        // Parsing input

        List <((int x, int y) start, (int x, int y) end)> lines = new ();

        foreach (var inputLine in input)
        {
            List<(int x, int y)> points = inputLine.Split(" -> ")
                .Select(line => line
                    .Split(",")
                    .Select(num => Int32.Parse(num))
                )
                .Select(coords => coords.ToList())
                .Select(coords => (x: coords[0], y: coords[1]))
                .ToList();

            foreach (var point in points)
            {
                minX = Math.Min(minX, point.x);
                maxX = Math.Max(maxX, point.x);
                maxY = Math.Max(maxY, point.y);
            }

            for (var i = 1; i < points.Count; i++)
            {
                lines.Add((start: points[i-1], end: points[i]));
            }
        }
        
        if (DEBUG) Console.WriteLine($"Done reading {lines.Count} lines");
        if (DEBUG) Console.WriteLine($"MinX: {minX}, MaxX: {maxX}, MinY: {minY}, MaxY: {maxY}");

        var cave = new Grid<char>(maxX + 1, maxY + 1, AIR);
        foreach (var line in lines)
        {
            // Put every line in the grid
            for (var column = line.start.x; column <= line.end.x; column++)
            {
                for (var row = line.start.y; row <= line.end.y; row++)
                {
                    cave.Set(row, column, ROCK);
                }
                for (var row = line.start.y; row >= line.end.y; row--)
                {
                    cave.Set(row, column, ROCK);
                }
            }
            for (var column = line.start.x; column >= line.end.x; column--)
            {
                for (var row = line.start.y; row <= line.end.y; row++)
                {
                    cave.Set(row, column, ROCK);
                }
                for (var row = line.start.y; row >= line.end.y; row--)
                {
                    cave.Set(row, column, ROCK);
                }
            }
        }

        Print();
        
        // Part 1
        var sandUnits = 0;
        while (CanDrop())
        {
            sandUnits++;
            
            Print();
        }
        
        Console.WriteLine($"How many units of sand come to rest before sand starts flowing into the abyss below? {sandUnits}");

        bool CanDrop()
        {
            (int row, int column) GetNextPosition((int row, int column) position)
            {
                if (position.row + 1 < cave.Height && cave.Get(position.row + 1, position.column) == AIR)
                {   // spot below is vacant, so we can drop
                    return position with { row = position.row + 1 };
                }
                
                // Obviously the down position is taken, check left and down
                if (position.row + 1 < cave.Height && position.column - 1 >= 0 && cave.Get(position.row + 1, position.column - 1) == AIR)
                {
                    return position with { column = position.column - 1, row = position.row + 1 };
                }
                
                // Obviously the down position is taken, check left and down
                if (position.row + 1 < cave.Height && position.column + 1 < cave.Width && cave.Get(position.row + 1, position.column + 1) == AIR)
                {
                    return position with { column = position.column + 1, row = position.row + 1 };
                }

                return position;
            }
            
            // starting from column = 500, row = 0
            (int row, int column) position = (row: 0, column: 500);
            var next = GetNextPosition(position);
            while (next != position) {
                position = next;

                if (position.row >= cave.Height - 1)
                {
                    // The sand unit has fallen into the void
                    return false;
                }

                next = GetNextPosition(position);
            }
            
            cave.Set(position.row, position.column, SAND);
            return true;
        }
        
        void Print()
        {
            if (!DEBUG) return;

            if (ANIMATE)
            {
                Console.Clear();
                Console.SetCursorPosition(0, 0);    
            }
            
            for (var row = 0; row < cave.Height; row++)
            {
                var line = "";
                for (var column = minX; column < cave.Width; column++)
                {
                    if (column == 500 && row == 0)
                    {
                        line += SOURCE;
                    }
                    else
                    {
                        line += cave.Get(row, column);
                    }
                }
                Console.WriteLine(line);
            }
            
            Console.WriteLine("");
            if (ANIMATE) Thread.Sleep(100);
        }
    }

    private class Grid<T>
    {
        public readonly int Width;
        public readonly int Height;
        private T[] _grid;
        
        public Grid(int width, int height, T def)
        {
            Width = width;
            Height = height;
            _grid = Enumerable.Repeat(def, width * height).ToArray();
        }

        public void Set(int row, int column, T value)
        {
            _grid[row * Width + column] = value;
        }
        
        public T Get(int row, int column)
        {
            return _grid[row * Width + column];
        }
    }
}