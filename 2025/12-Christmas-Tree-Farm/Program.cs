using System.Reflection;
using System.Text.RegularExpressions;
using _12_Christmas_Tree_Farm;
using Shared;

string path = Path.GetDirectoryName(Assembly.GetExecutingAssembly().Location) ?? ".";
string[] input = File.ReadAllLines(Environment.GetEnvironmentVariable("INPUT") != null ? $"{path}/{Environment.GetEnvironmentVariable("INPUT")}" : $"{path}/input.txt");

// Parse input
var regions = input.Where(line => Regex.IsMatch(line, "^[0-9]+x[0-9]+")).Select(Region.FromInput).ToArray();
var shapes = ParseShapes(input).ToArray();

List<Shape> ParseShapes(string[] input)
{
    var shapes = new List<Shape>();

    var current = new List<string>();
    foreach (var line in input)
    {
        if (line == "")
        {
            if (current.Count > 0) shapes.Add(new Shape(current.ToArray()));
        } else if (Regex.IsMatch(line, "^[0-9]+:"))
        {
            current.Clear();
        } else if (Regex.IsMatch(line, "^[0-9]+x[0-9]+"))
        {
            break; // we've reached the other part of the input
        }
        else
        {
            current.Add(line);
        }
    }
    
    return shapes;
}

// Part 01
var timer = new AocTimer();
// perform calculation
var one = PartOne(regions, shapes);
timer.Duration($"How many of the regions can fit all of the presents listed? {one}");

long PartOne(Region[] regions, Shape[] shapes)
{
    var t1 = 0;
    
    foreach (var region in regions)
    {
        var area = region.Width * region.Height;

        var s1 = 0;
        for (int r = 0; r < region.Counts.Length; r++)
        {
            s1 += region.Counts[r] * shapes[r].Populated;
        }

        if (s1 < area) t1++;
    }

    return t1;
}
