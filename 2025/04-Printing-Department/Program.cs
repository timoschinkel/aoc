using Shared;

string[] input = File.ReadAllLines(Environment.GetEnvironmentVariable("INPUT") != null ? $"./{Environment.GetEnvironmentVariable("INPUT")}" : "./input.txt");

// Parse input
char[][] grid = input.Select(line => line.ToCharArray()).ToArray();

// Part 01
var timer = new AocTimer();
long one = PartOne(grid);
timer.Duration($"How many rolls of paper can be accessed by a forklift? {one}");

// Part 02
timer = new AocTimer();
long two = PartTwo(grid);
timer.Duration($"How many rolls of paper in total can be removed by the Elves and their forklifts? {two}");

timer = new AocTimer();
two = PartTwoOptimized(grid);
timer.Duration($"How many rolls of paper in total can be removed by the Elves and their forklifts? {two}");

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
    long removed = 0;

    long lastRun = 0;
    do
    {
        (grid, lastRun) = RemovePaperRolls(grid);
        removed += lastRun;
    } while (lastRun > 0);
    
    return removed;
}

(char[][], long) RemovePaperRolls(char[][] grid)
{
    long removed = 0;
    char[][] cleaned = new char[grid.Length][];
    for (int y = 0; y < grid.Length; y++)
    {
        cleaned[y] = new char[grid[y].Length];
        for (int x = 0; x < grid[y].Length; x++)
        {
            if (grid[y][x] == '@' && CountNeighbors(grid, x, y) < 4)
            {
                removed++;
                cleaned[y][x] = '.';
            }
            else
            {
                cleaned[y][x] = grid[y][x];
            }
        }
    }
    
    return (cleaned, removed);
}

long PartTwoOptimized(char[][] grid)
{
    /*
     * I took my default approach of implementing this by following the instructions. That proved to be fast enough.
     *
     * Can this be optimized? I think it can. I found this really nice visualization[1], which uses the idea of using a
     * floodfill approach. If you take a first pass where you count the neighbors, and putting all the coordinates that are
     * to be removed on a stack, then you can iterate over the stack and reduce the number of neighbors with 1. If the
     * number of neighbors drops below the threshold, then we add it to the stack. Repeat this until the stack is empty.
     *
     * This approach is 4 to 5 times faster than my initial solution for part 2.
     *
     * [1]: https://www.reddit.com/r/adventofcode/comments/1pdt3u5/2025_day_4_part_2_decided_to_make_a_visualization/
     */

    long removed = 0;
    Stack<(int, int)> stack = new Stack<(int, int)>();
    
    int[][] neighbors = new int[grid.Length][];
    for (int y = 0; y < grid.Length; y++)
    {
        neighbors[y] = new int[grid[y].Length];
        for (int x = 0; x < grid[y].Length; x++)
        {
            neighbors[y][x] = grid[y][x] == '@' ? CountNeighbors(grid, x, y) : 0;
            if (grid[y][x] == '@' && neighbors[y][x] < 4)
            {   
                stack.Push((x, y));
            }
        }
    }

    while (stack.Count > 0)
    {
        var (x, y) = stack.Pop();
        
        // we can remove this paper roll
        removed++;
        neighbors[y][x] = 0;
        
        // decrease surrounding neighbor counts with 1,
        foreach (var (nx, ny) in GetNeighbors(grid, x, y))
        {
            // if neighbor count is below threshold (4) AND not already on stack
            // add it to the stack
            if (neighbors[ny][nx] > 0)
            {
                neighbors[ny][nx]--;
                if (neighbors[ny][nx] == 3)
                {
                    stack.Push((nx, ny));
                }
            }
        }
    }

    return removed;
}

IEnumerable<(int, int)> GetNeighbors(char[][] grid, int x, int y)
{
    List<(int, int)> neighbors = new List<(int, int)>();
    if (y >= 1)
    {
        if (x >= 1) neighbors.Add((x - 1, y - 1));
        neighbors.Add((x, y - 1));
        if (x < grid[y].Length - 1) neighbors.Add((x + 1, y - 1));
    }
    
    if (x >= 1) neighbors.Add((x - 1, y));
    if (x < grid[y].Length - 1) neighbors.Add((x + 1, y));
    
    if (y < grid.Length - 1)
    {
        if (x >= 1) neighbors.Add((x - 1, y + 1));
        neighbors.Add((x, y + 1));
        if (x < grid[y].Length - 1) neighbors.Add((x + 1, y + 1));
    }
    
    return neighbors;
}
