using System.Reflection;

namespace Day12;

class Program
{
    public static void Main(string[] args)
    {
        bool DEBUG = args.Contains("debug");
        
        string[] input = File.ReadAllLines($"{Path.GetDirectoryName(Assembly.GetExecutingAssembly().Location)}/input.txt");

        string alphabet = "abcdefghijklmnopqrstuvwxyz";

        // read input
        int width = input[0].Length;
        int height = input.Length;

        Grid<int> grid = new Grid<int>(width, height);
        Grid<int> steps = new Grid<int>(width, height);

        Coordinate start = new Coordinate { Row = 0, Column = 0 };
        Coordinate end = new Coordinate { Row = 0, Column = 0 };
        
        for (var row = 0; row < input.Length; row++)
        {
            var line = input[row];
            for (var column = 0; column < line.Length; column++)
            {
                steps.Set(row, column, Int32.MaxValue); // necessary? I don't know

                var character = line[column];
                if (character == 'S')
                {
                    start.Column = column;
                    start.Row = row;
                    character = 'a';
                    steps.Set(row, column, 0);
                }

                if (character == 'E')
                {
                    end.Column = column;
                    end.Row = row;
                    character = 'z';
                }
                
                var h = alphabet.IndexOf(character);
                grid.Set(row, column, h);
            }
        }
        
        // Part 1; Start at the starting point, administrate the path we took to get there - which is an empty list - 
        // and from that path we can determine the amount of steps it took to get there. We check all four possible 
        // neighbors and check if 1. the value is <= value + 1 2. we have not visited in less steps. If those criteria
        // match we add the neighbor on a queue and after checking all four neighbors we process the queue.

        var initialGrid = (Grid<int>)grid.Clone();
        var initialSteps = (Grid<int>)steps.Clone();
        initialSteps.Set(start, Int32.MaxValue);
        
        var stack = new Stack<Coordinate>();
        stack.Push(start);

        while (stack.Count > 0)
        {
            var candidate = stack.Pop();

            if (candidate.Row == end.Row && candidate.Column == end.Column)
            {
                continue;
            }
            
            var v = grid.Get(candidate);
            var s = steps.Get(candidate);
            
            if (DEBUG) Console.WriteLine($"Handling {candidate} (height: {v}, steps: {s})");

            // Look up
            var up = new Coordinate { Row = candidate.Row - 1, Column = candidate.Column };
            if (candidate.Row > 0 
                && grid.Get(up) <= grid.Get(candidate) + 1
                && steps.Get(up) > steps.Get(candidate) + 1)
            {
                if (DEBUG) Console.WriteLine($"Add up (height: {grid.Get(up)}, steps: {steps.Get(up)})");
                steps.Set(up, steps.Get(candidate) + 1);
                stack.Push(up);
            }
            
            // Look right
            var right = new Coordinate { Row = candidate.Row, Column = candidate.Column + 1 };
            if (right.Column < width 
                && grid.Get(right) <= grid.Get(candidate) + 1
                && steps.Get(right) > steps.Get(candidate) + 1)
            {
                if (DEBUG) Console.WriteLine($"Add right (height: {grid.Get(right)}, steps: {steps.Get(right)})");
                steps.Set(right, steps.Get(candidate) + 1);
                stack.Push(right);
            }
            
            // Look down
            var down = new Coordinate { Row = candidate.Row + 1, Column = candidate.Column };
            if (down.Row < height 
                && grid.Get(down) <= grid.Get(candidate) + 1
                && steps.Get(down) > steps.Get(candidate) + 1)
            {
                if (DEBUG) Console.WriteLine($"Add down (height: {grid.Get(down)}, steps: {steps.Get(down)})");
                steps.Set(down, steps.Get(candidate) + 1);
                stack.Push(down);
            }
            
            // Look left
            var left = new Coordinate { Row = candidate.Row, Column = candidate.Column - 1 };
            if (candidate.Column > 0 
                && grid.Get(left) <= grid.Get(candidate) + 1
                && steps.Get(left) > steps.Get(candidate) + 1)
            {
                if (DEBUG) Console.WriteLine($"Add left (height: {grid.Get(left)}, steps: {steps.Get(left)})");
                steps.Set(left, steps.Get(candidate) + 1);
                stack.Push(left);
            }
        }
        
        Print();
        Console.WriteLine($"What is the fewest steps required to move from your current position to the location that should get the best signal? {steps.Get(end)}");
        
        // Part 2; Given that part 1 is already solved there are two approaches to solve part 2. The first - naive -
        // approach is to iterate over all positions in heightmap that are of height `a`, and run the solution from 
        // part 1 and take the lowest value. Another approach is to reverse the solution. So start at the position 
        // marked as E, put eligible positions on the stack until you have exhausted the stack. Take the lowest number 
        // of steps to get to an `a` level coordinate.

        // Naive solution:
        
        var min = Int32.MaxValue;
        for (var row = 0; row < height; row++)
        {
            for (var column = 0; column < width; column++)
            {
                if (initialGrid.Get(row, column) == 0) // 0 == a
                {
                    grid = (Grid<int>)initialGrid.Clone();
                    steps = (Grid<int>)initialSteps.Clone();
                    
                    steps.Set(row, column, 0);
                    
                    stack = new Stack<Coordinate>();
                    stack.Push(new Coordinate{ Row = row, Column = column });
        
                    while (stack.Count > 0)
                    {
                        var candidate = stack.Pop();
        
                        if (candidate.Row == end.Row && candidate.Column == end.Column)
                        {
                            continue;
                        }
                        
                        var v = grid.Get(candidate);
                        var s = steps.Get(candidate);
                        
                        if (DEBUG) Console.WriteLine($"Handling {candidate} (height: {v}, steps: {s})");
        
                        // Look up
                        var up = new Coordinate { Row = candidate.Row - 1, Column = candidate.Column };
                        if (candidate.Row > 0 
                            && grid.Get(up) <= grid.Get(candidate) + 1
                            && steps.Get(up) > steps.Get(candidate) + 1)
                        {
                            if (DEBUG) Console.WriteLine($"Add up (height: {grid.Get(up)}, steps: {steps.Get(up)})");
                            steps.Set(up, steps.Get(candidate) + 1);
                            stack.Push(up);
                        }
                        
                        // Look right
                        var right = new Coordinate { Row = candidate.Row, Column = candidate.Column + 1 };
                        if (right.Column < width 
                            && grid.Get(right) <= grid.Get(candidate) + 1
                            && steps.Get(right) > steps.Get(candidate) + 1)
                        {
                            if (DEBUG) Console.WriteLine($"Add right (height: {grid.Get(right)}, steps: {steps.Get(right)})");
                            steps.Set(right, steps.Get(candidate) + 1);
                            stack.Push(right);
                        }
                        
                        // Look down
                        var down = new Coordinate { Row = candidate.Row + 1, Column = candidate.Column };
                        if (down.Row < height 
                            && grid.Get(down) <= grid.Get(candidate) + 1
                            && steps.Get(down) > steps.Get(candidate) + 1)
                        {
                            if (DEBUG) Console.WriteLine($"Add down (height: {grid.Get(down)}, steps: {steps.Get(down)})");
                            steps.Set(down, steps.Get(candidate) + 1);
                            stack.Push(down);
                        }
                        
                        // Look left
                        var left = new Coordinate { Row = candidate.Row, Column = candidate.Column - 1 };
                        if (candidate.Column > 0 
                            && grid.Get(left) <= grid.Get(candidate) + 1
                            && steps.Get(left) > steps.Get(candidate) + 1)
                        {
                            if (DEBUG) Console.WriteLine($"Add left (height: {grid.Get(left)}, steps: {steps.Get(left)})");
                            steps.Set(left, steps.Get(candidate) + 1);
                            stack.Push(left);
                        }
                    }
        
                    min = Math.Min(min, steps.Get(end));
                }
            }
        }
        
        Console.WriteLine($"What is the fewest steps required to move starting from any square with elevation a to the location that should get the best signal? {min}");

        void Print()
        {
            if (!DEBUG) return;

            for (var row = 0; row < height; row++)
            {
                var r1 = "";
                var r2 = "";
                for (var column = 0; column < width; column++)
                {
                    r1 += (
                        row == start.Row && column == start.Column ? 'S' : 
                        row == end.Row && column == end.Row ? 'E' : alphabet[grid.Get(row, column)]
                    );
                    r2 += steps.Get(row, column) == Int32.MaxValue ? "." : steps.Get(row, column);
                }
                Console.WriteLine($"{r1}    {r2}");
            }
        }
    }
    
    private class Coordinate
    {
        public int Row { get; set; }
        public int Column { get; set; }

        public override string ToString()
        {
            return $"column: {Column}, row: {Row}";
        }
    }

    private class Grid<T> : ICloneable
    {
        private readonly int _width;
        private T[] _grid;
        
        public Grid(int width, int height)
        {
            _width = width;
            _grid = new T[width * height];
        }

        private Grid(int width, T[] values)
        {
            _width = width;
            _grid = values;
        }

        public void Set(int row, int column, T value)
        {
            _grid[row * _width + column] = value;
        }

        public void Set(Coordinate c, T value)
        {
            Set(c.Row, c.Column, value);
        }

        public T Get(int row, int column)
        {
            return _grid[row * _width + column];
        }

        public T Get(Coordinate c)
        {
            return Get(c.Row, c.Column);
        }

        public object Clone()
        {
            return new Grid<T>(_width, new List<T>(_grid).ToArray());
        }
    }
}