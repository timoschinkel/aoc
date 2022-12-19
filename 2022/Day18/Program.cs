using System.Diagnostics;
using System.Reflection;

namespace Day18;

class Program
{
    public static void Main(string[] args)
    {
        bool DEBUG = args.Contains("debug");
        bool EXAMPLE = args.Contains("example");

        string[] input = EXAMPLE
            ? File.ReadAllLines($"{Path.GetDirectoryName(Assembly.GetExecutingAssembly().Location)}/example.txt")
            : File.ReadAllLines($"{Path.GetDirectoryName(Assembly.GetExecutingAssembly().Location)}/input.txt");

        // Read input
        Dictionary<(int x, int y, int z), int> cubes = input
            .Select(line => line.Split(",").Select(coordinate => Int32.Parse(coordinate)).ToArray())
            .Select(coords => (x: coords[0], y: coords[1], z: coords[2]))
            .ToDictionary(coords => coords, coords => 0);

        var timer = new Stopwatch();
        timer.Start();
        
        // Part 1:

        var deltas = new List<(int x, int y, int z)>
        {
            (1, 0, 0),
            (-1, 0, 0),
            (0, 1, 0),
            (0, -1, 0),
            (0, 0, 1),
            (0, 0, -1),
        };

        long exposed = 0;
        foreach (var entry in cubes)
        {
            var cube = entry.Key;

            foreach (var delta in deltas)
            {
                // if there is a cube at cube + delta, then it is NOT exposed
                var neighbor = (x: cube.x + delta.x, y: cube.y + delta.y, z: cube.z + delta.z);
                if (cubes.ContainsKey(neighbor) == false)
                {
                    exposed++;
                }
            }
        }
        timer.Stop();
        
        Console.WriteLine($"What is the surface area of your scanned lava droplet? {exposed} ({timer.ElapsedMilliseconds}ms)");
    }
}