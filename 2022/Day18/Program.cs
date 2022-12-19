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
        
        // Part 1: I went with the "brute force" approach; Iterate over every cube and check if there is a neighbor for
        // it. I try to optimize this by storing the cubes in a dictionary with its coordinates as key. Checking for 
        // neighbors is done by looking at all six sides using a set of delta. If a side is not occupied add one to the
        // number of outside faces sides.
        //
        // Part 2: We only need the outward facing surfaces. I determine the boundaries of the droplet by iterating over
        // all cubes. Then I start a x = minx - 1, y = miny - 1, z = minz - 1 and using a flood fill I check every cube
        // on the outer border. Your boundaries should extend the min/max values with 1 to make sure you can walk around
        // any bulges. The flood fill will keep a list (dictionary for speed improvement) of visited coordinates. Every 
        // cube is inspected, if it borders a cube increment the outside facings with 1, if it does not border a cube
        // add the neighboring cube to the flood fill stack. Rinse and repeat until all outside coordinates are visited.

        var deltas = new List<(int x, int y, int z)>
        {
            (1, 0, 0),
            (-1, 0, 0),
            (0, 1, 0),
            (0, -1, 0),
            (0, 0, 1),
            (0, 0, -1),
        };
        
        // Part 1:

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
        
        // Part 2
        timer.Restart();
        int minx = cubes.First().Key.x, maxx = cubes.First().Key.x;
        int miny = cubes.First().Key.y, maxy = cubes.First().Key.y;
        int minz = cubes.First().Key.z, maxz = cubes.First().Key.z;

        foreach (var entry in cubes)
        {
            minx = Math.Min(minx , entry.Key.x);
            maxx = Math.Max(maxx , entry.Key.x);
            miny = Math.Min(miny , entry.Key.y);
            maxy = Math.Max(maxy , entry.Key.y);
            minz = Math.Min(minz , entry.Key.z);
            maxz = Math.Max(maxz , entry.Key.z);
        }

        long outside = 0;
        Dictionary<(int x, int y, int z), int> visited = new();
        Stack<(int x, int y, int z)> stack = new();
        stack.Push((x: minx - 1, y: miny - 1, z: minz - 1));
        
        while (stack.Count > 0)
        {
            var cube = stack.Pop();
            if (visited.ContainsKey(cube)) continue;
            
            visited.Add(cube, 0);
            
            // check all sides, if not empty: found an outside edge
            // if empty and not yet visited: add to stack, given it is within boundaries
            foreach (var delta in deltas)
            {
                var neighbor = (x: cube.x + delta.x, y: cube.y + delta.y, z: cube.z + delta.z);
                
                if (neighbor.x < minx - 1 || neighbor.x > maxx + 1 ||
                    neighbor.y < miny - 1 || neighbor.y > maxy + 1 ||
                    neighbor.z < minz - 1 || neighbor.z > maxz + 1)
                {
                    // out of bounds
                    continue;
                }
                
                if (cubes.ContainsKey(neighbor))
                {
                    outside++;
                    continue;
                }

                if (visited.ContainsKey(neighbor) == false)
                {
                    stack.Push(neighbor);
                }
            }
        }
        
        timer.Stop();
        Console.WriteLine($"Number of exposed sides: {outside} ({timer.ElapsedMilliseconds}ms)");
    }
}