using System.Diagnostics;

string[] input = File.ReadAllLines(Environment.GetEnvironmentVariable("INPUT") != null ? $"./{Environment.GetEnvironmentVariable("INPUT")}" : "./input.txt");

// Parse input
List<(long, long)> ranges = new List<(long, long)>();
List<long> ingredients = new List<long>();

foreach (var line in input)
{
    if (line.Contains("-"))
    {
        ranges.Add((long.Parse(line.Split('-')[0]), long.Parse(line.Split('-')[1])));
    } else if (line.Length > 0)
    {
        ingredients.Add(long.Parse(line));       
    }
}

// Part 01
var sw = Stopwatch.StartNew();
// perform calculation
long one = PartOne(ranges, ingredients);
sw.Stop();
PrintElapsedTime($"How many of the available ingredient IDs are fresh? {one} ([ELAPSED])", sw.Elapsed);

// Part 02
sw = Stopwatch.StartNew();
// perform calculation
long two = PartTwo(ranges);
sw.Stop();
PrintElapsedTime($"How many ingredient IDs are considered to be fresh according to the fresh ingredient ID ranges? {two} ([ELAPSED])", sw.Elapsed);

long PartOne(List<(long, long)> ranges, List<long> ingredients)
{
    long fresh = 0;
    foreach (var ingredient in ingredients)
    {
        if (IsInRanges(ingredient, ranges))
        {
            fresh += 1;
        }
    }
    return fresh;
}

bool IsInRanges(long ingredient, List<(long, long)> ranges)
{
    foreach (var range in ranges)
    {
        if (ingredient >= range.Item1 && ingredient <= range.Item2)
        {
            return true;
        }
    }

    return false;
}

long PartTwo(List<(long, long)> ranges)
{
    List<(long, long)> clean = new List<(long, long)>();
    
    // We are going to try to find overlaps
    foreach (var (start, end) in ranges)
    {
        clean.AddRange(GetNonOverlapping(clean, start, end));    
    }

    long ingredients = 0;
    foreach (var (start, end) in clean)
    {
        ingredients += (end - start + 1);
    }
    
    return ingredients;
}

List<(long, long)> GetNonOverlapping(List<(long, long)> clean, long start, long end)
{
    List<(long, long)> nonOverlapping = new List<(long, long)>();
    
    Stack<(long, long)> stack = new Stack<(long, long)>();
    stack.Push((start, end));

    while (stack.Count > 0)
    {
        (long, long) current = stack.Pop();

        bool isCurrentUsable = true;
        foreach (var (rangeStart, rangeEnd) in clean)
        {
            if (!isCurrentUsable) continue;

            var currentStart = current.Item1;
            var currentEnd = current.Item2;
            
            // current      |----|
            // range    |------------|
            if (currentStart >= rangeStart && currentEnd <= rangeEnd)
            {
                // completely overlapping, we can remove this range completely
                isCurrentUsable = false;
                continue;
            }

            // current       |----|
            // range                |----|
            // or      |---|
            if (currentEnd < rangeStart || currentStart > rangeEnd)
            {
                // no overlap, so early exit
                continue;
            }

            // The following scenario is caught by the two following scenarios
            // current  |--------------|
            // range        |----|
            
            // current      |-----|
            // range    |-----|
            if (currentStart >= rangeStart && currentStart <= rangeEnd)
            {
                // push overlap for future evaluation
                stack.Push((currentStart, rangeEnd - 1));
                current = (rangeEnd + 1, currentEnd);
            }
            
            // current  |-----|
            // range        |-----|
            if (currentStart < rangeStart && currentStart <= rangeEnd)
            {
                stack.Push((rangeStart, currentEnd));
                current = (currentStart, rangeStart - 1);
            }
        }
        
        // if we reach the end of the loop, then we have no overlap with other ranges and thus is it "clean"
        if (isCurrentUsable)
        {
            nonOverlapping.Add(current);
        }
    }
    
    return nonOverlapping;
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
