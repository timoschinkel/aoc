using System.ComponentModel;
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
        
        Console.WriteLine($"In the row where y=2000000, how many positions cannot contain a beacon? {p1}");
        
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