using System.Collections;
using System.Collections.Immutable;
using System.Diagnostics;
using System.Reflection;
using System.Text.RegularExpressions;

namespace Day16;

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
        Dictionary<string, Valve> valves = input
            .Select(line => Regex.Match(line,
                @"^Valve (?<name>[A-Z]{2}) has flow rate=(?<flowrate>[0-9]+); tunnels? leads? to valves? (?<tunnels>([A-Z]{2}, )*[A-Z]{2})$"))
            .Select(match => new Valve(match.Groups["name"].Value, Int32.Parse(match.Groups["flowrate"].Value),
                match.Groups["tunnels"].Value.Split(", ")))
            .ToDictionary(valve => valve.Name, valve => valve);

        var timer = new Stopwatch();
        
        // Part 1; Wow, this was a tricky one, at least for me... I needed some tips and tricks to solve this one. 
        // The apparent key lies in making the problem smaller; Instead of making a decision to open a valve or continue
        // to another can be removed by taking the valves without flow rate out of the equation. To do this I calculate
        // the shortest paths to get from any non-zero valve to any other non-zero valve using BFS/DFS. This leads to a
        // new _graph_ where we always open the valve we visit. Since the data set is relatively small it is possible to
        // perform a "brute force" recursive solution to find the most efficient path.
        
        timer.Start();
        CalculateShortestPaths();
        long maximumPressure = FindMaximumPressure(30, "AA", ImmutableList<string>.Empty);
        timer.Stop();
        
        Console.WriteLine($"What is the most pressure you can release? {maximumPressure} ({timer.ElapsedMilliseconds}ms)");

        long FindMaximumPressure(int minutesLeft, string current, ImmutableList<string> open)
        {
            long best = 0;
            foreach (var entry in valves[current].ShortestRelevantPaths)
            {
                var v = entry.Key;
                var steps = entry.Value;
                var newMinutesLeft = minutesLeft - steps - 1; /* we need 1 minute to open valve */
                
                if (newMinutesLeft > 0 && open.Contains(v) == false)
                {
                    long pressure = newMinutesLeft * valves[v].FlowRate + FindMaximumPressure(newMinutesLeft, v, open.Add(v));
                    if (pressure > best)
                    {
                        best = pressure;
                    }
                }
            }

            return best;
        }
        
        void CalculateShortestPaths()
        {
            foreach (var entry in valves)
            {
                var valve = entry.Value;
                if (valve.Name != "AA" && valve.FlowRate == 0) continue; // irrelevant
                
                // Perform a BFS to find the shortest paths to all relevant valves
                Dictionary<string, int> paths = new();
                paths[valve.Name] = 0;
                
                Stack<string> stack = new Stack<string>();
                stack.Push(valve.Name);

                while (stack.Count > 0)
                {
                    var current = valves[stack.Pop()];
                    var pathToCurrent = paths[current.Name];

                    // Find all neighbors:
                    foreach (var tunnel in current.Tunnels)
                    {
                        if (paths.GetValueOrDefault(tunnel, Int32.MaxValue) > pathToCurrent + 1)
                        {
                            paths[tunnel] = pathToCurrent + 1;
                            stack.Push(tunnel);
                        }
                    }
                }

                foreach (var e in paths)
                {
                    if (valves[e.Key].FlowRate > 0 && e.Key != valve.Name)
                    {
                        valve.ShortestRelevantPaths[e.Key] = e.Value;
                    }
                }
            }
        }
    }

    private class Valve
    {
        public readonly string Name;
        public readonly int FlowRate;
        public readonly string[] Tunnels;

        // A dictionary with the shortest paths from this valve to other valves. Key is the valve name, value is distance.
        public readonly Dictionary<string, int> ShortestRelevantPaths = new();

        public Valve(string name, int flowRate, string[] tunnels)
        {
            Name = name;
            FlowRate = flowRate;
            Tunnels = tunnels;
        }
    }
}