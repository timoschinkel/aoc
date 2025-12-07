using Shared;

string[] input = File.ReadAllLines(Environment.GetEnvironmentVariable("INPUT") != null ? $"./{Environment.GetEnvironmentVariable("INPUT")}" : "./input.txt");

// Parse input

// Part 01
var timer = new AocTimer();
// perform calculation
var one = PartOne(input);
timer.Duration($"How many times will the beam be split? {one}");

// Part 02
timer = new AocTimer();
// perform calculation
var two = PartTwo(input);
timer.Duration($"In total, how many different timelines would a single tachyon particle end up on? {two}");

long PartOne(string[] grid)
{
    // Find S
    var s = FindS(grid.First());
    long splits = 0;
    
    // We are going top to bottom, so why not keep track of the beams per line
    HashSet<int> beams = new HashSet<int>{ s };
    for (int y = 2; y < grid.Length; y+=2) // since only every other line contains splitters, we can take steps of 2 rows
    {
        HashSet<int> next = new HashSet<int>();
        foreach (var beam in beams) 
        {
            if (grid[y][beam] == '^')
            {
                splits++;
                // I have inspected the input, no splitters are at the edges
                next.Add(beam - 1);
                next.Add(beam + 1);
            }
            else
            {   // No splitter, so the beam continues
                next.Add(beam);
            }
        }

        beams = next;
    }
    
    return splits;
}

long PartTwo(string[] grid)
{
    // Find S
    var s = FindS(grid.First());
    
    /*
     * Whenever we split a beam, we can two timelines; one on the left and one on the right. So a single beam with a
     * single splitter causes two timelines - see line 2. Line 4 is where the tricky part comes in play; for the center
     * index there is one timeline coming from the beam going left - right and one from the beam going right - left. We
     * need to add those together. We continue this until we reach the end, and then all we need to do is add all
     * timelines to get the answer.
     * 
     * 0    .......S.......         1
     * 1    .......1.......         1
     * 2    ......1^1......         2
     * 3    ......1.1......         2
     * 4    .....1^2^1.....         4
     */
    Dictionary<int, long> beams = new Dictionary<int, long>();
    beams[s] = 1; // one timeline
    
    for (int y = 2; y < grid.Length; y+=2) // since only every other line contains splitters, we can take steps of 2 rows
    {
        Dictionary<int, long> next = new Dictionary<int, long>();
        foreach (var (beam, timelines) in beams) 
        {
            if (grid[y][beam] == '^')
            {
                // I have inspected the input, no splitters are at the edges
                next[beam - 1] = timelines + next.GetValueOrDefault(beam - 1, 0);
                next[beam + 1] = timelines + next.GetValueOrDefault(beam + 1, 0);
            }
            else
            {   // No splitter, so the beam continues
                next[beam] = timelines + next.GetValueOrDefault(beam, 0);
            }
        }

        beams = next;
    }
    
    return beams.Sum(beam => beam.Value);
}

int FindS(string line)
{
    for (int i = 0; i < line.Length; i++)
    {
        if (line[i] == 'S') return i;
    }
    
    throw new Exception("S not found");
}