using System.Reflection;
using Shared;

string path = Path.GetDirectoryName(Assembly.GetExecutingAssembly().Location) ?? ".";
string[] input = File.ReadAllLines(Environment.GetEnvironmentVariable("INPUT") != null ? $"{path}/{Environment.GetEnvironmentVariable("INPUT")}" : $"{path}/input.txt");

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
var timer = new AocTimer();
// perform calculation
long one = PartOne(ranges, ingredients);
timer.Duration($"How many of the available ingredient IDs are fresh? {one}");

// Part 02
timer = new AocTimer();
// perform calculation
long two = PartTwo(ranges);
timer.Duration($"How many ingredient IDs are considered to be fresh according to the fresh ingredient ID ranges? {two}");

// Part 02 Optimized
timer = new AocTimer();
// perform calculation
two = PartTwoOptimized(ranges);
timer.Duration($"How many ingredient IDs are considered to be fresh according to the fresh ingredient ID ranges? {two}");

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

long PartTwoOptimized(List<(long, long)> ranges)
{
    /*
     * The solutions on Reddit showed an alternative approach which is a lot more straightforward; what if we order the
     * ranges based on their starting value. Then we can iterate over the ranges and compare the current position with
     * the start position of the next range. If the start of the starting range is larger than our current position, then
     * there is no overlap. We can count the number of items in the range and update the current position to the end of
     * the range. Otherwise there _is_ an overlap, and we take the ingredient ids that fit between the current position
     * and the new end.
     *
     * The funny thing is that this approach is not actually faster for me. I suspect due to the sorting algorithm.
     */
    
    // Sort the ranges based on their start value
    ranges.Sort((a, b) => a.Item1.CompareTo(b.Item1));

    var nonOverlappingRanges = new List<(long, long)>();
    
    // Merge overlapping ranges
    (long, long) previous = ranges.First();
    
    foreach (var current in ranges.Slice(1,  ranges.Count - 1))
    {
        if (current.Item1 <= previous.Item2)
        {
            // merge
            previous.Item2 = Math.Max(previous.Item2, current.Item2);
        }
        else
        {
            nonOverlappingRanges.Add(previous);
            previous = current;
        }
    }
    
    nonOverlappingRanges.Add(previous);
    
    long ingredients = 0;
    foreach (var (start, end) in nonOverlappingRanges)
    {
        ingredients += (end - start + 1);
    }
    
    return ingredients;
}
