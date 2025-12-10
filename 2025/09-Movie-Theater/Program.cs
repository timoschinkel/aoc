using _09_Movie_Theater;
using System.Reflection;
using Shared;

string path = Path.GetDirectoryName(Assembly.GetExecutingAssembly().Location) ?? ".";
string[] input = File.ReadAllLines(Environment.GetEnvironmentVariable("INPUT") != null ? $"{path}/{Environment.GetEnvironmentVariable("INPUT")}" : $"{path}/input.txt");

// Parse input
var tiles = input.Select(line => line.Split(',')).Select(item => new Point(long.Parse(item[0]), long.Parse(item[1]))).ToArray();

// Part 01
var timer = new AocTimer();
// perform calculation
var one = PartOne(tiles);
timer.Duration($"What is the largest area of any rectangle you can make? {one}");

// Part 02
timer = new AocTimer();
// perform calculation
var two = PartTwo(tiles);
timer.Duration($"What is the largest area of any rectangle you can make using only red and green tiles? {two}");

long PartOne(Point[] board)
{
    // Find the biggest square
    long max = 0;
    for (int i = 0; i < board.Length - 1; i++)
    {
        for (int j = i + 1; j < board.Length; j++)
        {
            long current = (Math.Abs(tiles[i].X - tiles[j].X) + 1) * (Math.Abs(tiles[i].Y - tiles[j].Y) + 1);
            if (current > max)
            {
                max = current;
            }
        }
    }
    return max;
}

long PartTwo(Point[] board)
{
    List<Edge> edges = new List<Edge>();
    
    for (int i = 0; i < board.Length - 1; i++)
    {
        // edge from board[i] to board[i + 1]
        edges.Add(new Edge(board[i].X, board[i].Y, board[i+1].X, board[i+1].Y));
    }
    edges.Add(new Edge(board[^1].X, board[^1].Y, board[0].X, board[0].Y));
    
    /*
     * > In your list, every red tile is connected to the red tile before and after it by a straight line of green tiles.
     *
     * The maximum area will always be between red tiles, so we only need to check the red tiles against each other. In
     * doing so we need to check if we are intersecting any of the edges. This could go wrong for some polygonal shapes,
     * but inspection of the input shows that the polygon is a convex shape, so the largest area would always be on the
     * inside.
     *
     * We will compare any two red tiles and see if they intersect with any of the edges. If they do, then we don't have
     * an area. Checking the intersection of a line and a rectangle is well described. 
     *
     * I came up with this approach, but was encouraged to implement it after seeing someone on Reddit using this as well.
     * See: https://www.reddit.com/r/adventofcode/comments/1pi3hff/2025_day_9_part_2_a_simple_method_spoiler/
     * See: https://github.com/blfuentes/AdventOfCode_AllYears/blob/main/AdventOfCode_2025_Go/day09/day09_2.go
     */

    long max = 0;
    for (int i = 0; i < board.Length - 1; i++)
    {
        for (int j = i + 1; j < board.Length; j++)
        {
            // Calculating the area is much cheaper than finding the intersection, so we first check for the area
            long current = (Math.Abs(board[i].X - board[j].X) + 1) * (Math.Abs(board[i].Y - board[j].Y) + 1);
            if (current > max)
            {
                if (!IntersectWithEdge(board[i], board[j], edges))
                {
                    max = current;
                }
            }
        }
    }

    return max;
}

bool IntersectWithEdge(Point one, Point another, List<Edge> edges)
{
    long minX = Math.Min(one.X, another.X);
    long maxX = Math.Max(one.X, another.X);
    long minY = Math.Min(one.Y, another.Y);
    long maxY = Math.Max(one.Y, another.Y);
    foreach (Edge edge in edges)
    {
        if (minX < edge.MaxX && maxX > edge.MinX && minY < edge.MaxY && maxY > edge.MinY)
        {
            //Console.WriteLine($"({one.X},{one.Y}) - ({another.X},{another.Y}) intersects with ({edge.X1},{edge.Y1}) - ({edge.X2},{edge.Y2})");
            return true;
        }
    }
    
    return false;
}