using System.Diagnostics;

string[] input = File.ReadAllLines(Environment.GetEnvironmentVariable("INPUT") != null ? $"./{Environment.GetEnvironmentVariable("INPUT")}" : "./input.txt");

// Parse input
char[][] grid = input.Select(line => line.ToCharArray()).ToArray();

// Part 01
var sw = Stopwatch.StartNew();
// perform calculation
long one = PartOne(grid);
sw.Stop();
PrintElapsedTime($"How many rolls of paper can be accessed by a forklift? {one} ([ELAPSED])", sw.Elapsed);

// Part 02
sw = Stopwatch.StartNew();
// perform calculation
long two = PartTwo(grid);
sw.Stop();
PrintElapsedTime($"The answer is: {two} ([ELAPSED])", sw.Elapsed);

long PartOne(char[][] grid)
{
    long paper = 0;
    for (int y = 0; y < grid.Length; y++)
    {
        for (int x = 0; x < grid[0].Length; x++)
        {
            if (grid[y][x] == '@' && CountNeighbors(grid, x, y) < 4)
            {
                paper++;
            }
        }
    }
    return paper;
}

int CountNeighbors(char[][] grid, int x, int y)
{
    int neighbors = 0;
    if (y >= 1 && x >= 1 && grid[y-1][x-1] == '@') neighbors++;
    if (y >= 1 && grid[y-1][x] == '@') neighbors++;
    if (y >= 1 && x < grid[y].Length - 1 && grid[y-1][x + 1] == '@') neighbors++;
    
    if (x >= 1 && grid[y][x - 1] == '@') neighbors++;
    if (x < grid[y].Length - 1 && grid[y][x + 1] == '@') neighbors++;
    
    if (y < grid.Length - 1 && x >= 1 && grid[y + 1][x - 1] == '@') neighbors++;
    if (y < grid.Length - 1 && grid[y + 1][x] == '@') neighbors++;
    if (y < grid.Length - 1 && x < grid[y].Length - 1 && grid[y+1][x + 1] == '@') neighbors++;
    
    return neighbors;
}

long PartTwo(char[][] grid)
{
    return 0;
}

void PrintElapsedTime(string message, TimeSpan ts)
{
    var ns = ts.TotalNanoseconds;
    var parts = message.Split("[ELAPSED]");
    for (var i = 0; i < parts.Length; i++)
    {
        Console.Write(parts[i]);
        if (i < parts.Length - 1)
        {
            if (ns > 1000000000)
            {
                Console.ForegroundColor = ConsoleColor.Red;
                Console.Write($"{Math.Round(ns / 1000000000, 2)}s");
            }
            else if (ns > 1000000)
            {
                Console.ForegroundColor = ConsoleColor.Yellow;
                Console.Write($"{Math.Ceiling(ns / 1000000)}ms");
            }
            else if (ns > 1000)
            {
                Console.ForegroundColor = ConsoleColor.Green;
                Console.Write($"{Math.Ceiling(ns / 1000)}μs");
            }
            else
            {
                Console.ForegroundColor = ConsoleColor.Green;
                Console.Write($"{ns}ns");
            }
            
            Console.ResetColor();
        }
    }
    
    Console.WriteLine("");
}
