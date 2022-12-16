using System.Diagnostics;
using System.Reflection;
using System.Text.RegularExpressions;

namespace Day15;

class Program
{
    public static void Main(string[] args)
    {
        bool DEBUG = args.Contains("debug");
        bool EXAMPLE = args.Contains("example");

        string[] input = EXAMPLE
            ? File.ReadAllLines($"{Path.GetDirectoryName(Assembly.GetExecutingAssembly().Location)}/example.txt")
            : File.ReadAllLines($"{Path.GetDirectoryName(Assembly.GetExecutingAssembly().Location)}/input.txt");
        int target = EXAMPLE ? 10 : 2000000;

        List<((int x, int y) sensor, (int x, int y) beacon, int manhattan)> readings = input
                .Select(line => Regex.Match(line,
                    @"^Sensor at x=(?<sensorx>[\-0-9]+), y=(?<sensory>[\-0-9]+): closest beacon is at x=(?<beaconx>[\-0-9]+), y=(?<beacony>[\-0-9]+)$"))
                .Where(match => match.Success)
                .Select(match => (
                    sensor: (x: Int32.Parse(match.Groups["sensorx"].Value), y: Int32.Parse(match.Groups["sensory"].Value)), 
                    beacon: (x: Int32.Parse(match.Groups["beaconx"].Value), y: Int32.Parse(match.Groups["beacony"].Value))
                ))
                .Select(reading => (reading.sensor, reading.beacon, manhattan: Manhattan(reading.sensor, reading.beacon)))
                .ToList()
            ;

        int minx = 0, maxx = 0, miny = 0, maxy = 0;
        foreach (var reading in readings)
        {
            minx = Math.Min(minx, reading.sensor.x - reading.manhattan);
            maxx = Math.Max(maxx, reading.sensor.x + reading.manhattan);
            miny = Math.Min(miny, reading.sensor.y - reading.manhattan);
            maxy = Math.Max(maxy, reading.sensor.y + reading.manhattan);
        }
        
        int Manhattan((int x, int y) one, (int x, int y) two)
        {
            return Math.Abs(one.x - two.x) + Math.Abs(one.y - two.y);
        }
        
        // Part 1; My knowledge of Manhattan Distance was non-existent, and that showed. My initial approach was to draw
        // the entire Manhattan plane on a grid and iterate over the y-axis. This worked great for the example, but took
        // way, and way too long for the actual puzzle input. The solution I opted for is to determine the minimal and
        // maximal values of x by subtracting and/or adding the Manhattan Distance to every x value of the sensors. The 
        //- as I understood from other - common mistake is that there are beacons that are on the y-axis that is the 
        // target. These need to be subtracted from the total amount. This solution runs in about 3 seconds on my 2020
        // MacBook Pro. I think there is an easy optimization to only iterate the maximum and minimal values of x per
        // sensor. 
        
        // Part 2; I needed some help on this one. I used the explanation about the characteristics of Manhattan
        // Distance (they are always diamond shapes) - thanks to Goleztrol - and I used a hint from a Reddit user.
        // Given that we are looking for a single coordinate within (0, 4000000) x (0, 4000000) looking at every
        // possible coordinate is not feasible. Given that there is exactly 1 coordinate that is *NOT* in one of the
        // Manhattan Distance diamonds of any of the sensors, then that coordinate MUST be next to an edge of a
        // Manhattan Distance. So if we iterate over the outer edges of all 32 reading, then we reduce the amount of
        // calculation dramatically.
        //
        // Post mortem; I was lacking knowledge of the characteristics of Manhattan Distance. I built part 1 with minor 
        // optimization. An easy optimization is not to iterate the entire y-axis from minx to maxx for every reading,
        // but to only iterate the minx and maxx for a specific reading. Even better would be to use math: Given 
        // Sensor S and Beacon B with a Manhattan Distance M, then the edges of the diamond shape are are S(x) - M
        // and S(x) + M. From S(y) every step upwards the width of the diamond shape reduces with 2 until S(y) - M and
        // S(y) + M, where the width is 1. With this knowledge it is much less calculations to determine the width of 
        // the Manhattan plane on target y-axis. That would leave out calculating intersections. I did not implement 
        // this optimization. For part 2 I think some optimization is possible, but maybe I got lucky and the solution
        // was found on the first reading, so I did not look into this further.

        var timer = new Stopwatch();
        timer.Start();
        
        List<int> impossible = new();
        for (var x = minx; x <= maxx; x++)
        {
            foreach (var reading in readings)
            {
                if (Manhattan(reading.sensor, (x, y: target)) <= reading.manhattan)
                {
                    impossible.Add(x);
                }
            }
        }

        // Remove any beacons from the impossible
        foreach (var reading in readings)
        {
            if (reading.beacon.y == target && impossible.Contains(reading.beacon.x))
            {
                impossible.RemoveAll(x => x == reading.beacon.x);
            }
        }

        Print();
        
        var p1 = impossible
            .Distinct()
            .Count();
        
        timer.Stop();
        Console.WriteLine($"In the row where y=2000000, how many positions cannot contain a beacon? {p1} ({timer.ElapsedMilliseconds}ms)");
        
        int region = EXAMPLE ? 20 : 4000000;
        (int x, int y) beacon = (x: 0, y: 0);

        timer.Restart();
        
        for (var i = 0; i < readings.Count; i++)
        {
            var coords = GetOuterEdge(readings[i]);
            
            //Console.WriteLine($"Handling sensor {i}, found {coords.Count} points");

            // Iterate every coordinate at the edge
            foreach (var point in coords)
            {
                if (point.x < 0 || point.x > region || point.y < 0 || point.y > region)
                {
                    // Point is not within the search region
                    continue;
                }

                if (IsWithinManhattanDistance(point, readings.Where(r => r != readings[i]).ToList()))
                {
                    // Within range of one of the other sensors
                    continue;
                }

                beacon = point;
                i = readings.Count + 1; // jump out of reading loop
                break;
            }
        }

        long score = (4000000 * (long)beacon.x) + (long)beacon.y;
        timer.Stop();
        Console.WriteLine($"What is its tuning frequency? {score} ({timer.ElapsedMilliseconds}ms)");

        bool IsWithinManhattanDistance((int x, int y) candidate, List<((int x, int y) sensor, (int x, int y) beacon, int manhattan)> others)
        {
            foreach (var reading in others)
            {
                if (Manhattan(candidate, reading.sensor) <= reading.manhattan)
                {
                    return true;
                }
            }
            
            return false;
        }
        
        List<(int x, int y)> GetOuterEdge(((int x, int y) sensor, (int x, int y) beacon, int manhattan) reading)
        {
            //      #2           M = 1
            //     ###1
            //    ##x##0
            //     ###
            //      #
            List<(int x, int y)> coords = new List<(int x, int y)>
            {
                reading.sensor with { y = reading.sensor.y - reading.manhattan - 1 }, // top
                reading.sensor with { x = reading.sensor.x + reading.manhattan + 1 }, // right
                reading.sensor with { y = reading.sensor.y + reading.manhattan + 1 }, // bottom
                reading.sensor with { x = reading.sensor.x - reading.manhattan - 1 }, // left
            };

            // Iterate over the right-top 
            for (var delta = 0; delta < reading.manhattan; delta++)
            {
                // We can use mirroring to determine all four
                coords.Add((x: reading.sensor.x + reading.manhattan - delta, y: reading.sensor.y - 1 - delta)); // top right
                coords.Add((x: reading.sensor.x - reading.manhattan + delta, y: reading.sensor.y - 1 - delta)); // top left
                coords.Add((x: reading.sensor.x - reading.manhattan + delta, y: reading.sensor.y + 1 + delta)); // bottom left
                coords.Add((x: reading.sensor.x + reading.manhattan - delta, y: reading.sensor.y + 1 + delta)); // bottom right
            }
            
            return coords;
        }
        
        void Print()
        {
            if (!DEBUG) return;

            Dictionary<(int x, int y), char> grid = new();

            // put readings in
            foreach (var reading in readings)
            {
                grid[(x: reading.sensor.x, y: reading.sensor.y)] = 'S';
                grid[(x: reading.beacon.x, y: reading.beacon.y)] = 'B';
            }
            
            foreach (var x in impossible)
            {
                grid[(x, y: target)] = '#';
            }
            
            // row = y
            // column = x
            for (var row = miny - 1; row <= maxy + 1; row++)
            {
                for (var column = minx - 1; column <= maxx + 1; column++)
                {
                    Console.Write(grid.ContainsKey((x: column, y: row)) ? grid[(x: column, y: row)] : (row == target ? '~' : '.'));
                }
                Console.WriteLine("");
            }
            Console.WriteLine("");
        }
    }
}