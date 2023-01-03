using System.Collections;
using System.Diagnostics;
using System.Reflection;
using System.Text.RegularExpressions;

namespace Day19;

class Program
{
    public static void Main(string[] args)
    {
        bool DEBUG = args.Contains("debug");
        bool EXAMPLE = args.Contains("example");

        string[] input = EXAMPLE
            ? File.ReadAllLines($"{Path.GetDirectoryName(Assembly.GetExecutingAssembly().Location)}/example.txt")
            : File.ReadAllLines($"{Path.GetDirectoryName(Assembly.GetExecutingAssembly().Location)}/input.txt");

        var timer = new Stopwatch();
        
        // Part 1; I have opted for a Depth First Search, where I try to limit the search paths. I only managed to do
        // partially. This solution is NOT fast, but it got me my star.
        //
        // Part 2; My approach from part 1 did not yield the correct answer, even after 45 minutes. This means I did not 
        // only need to limit the search paths, I also had to fix my solution. I looked at suggestions on Reddit about
        // how to limit the search paths and was greatly helped by https://www.reddit.com/r/adventofcode/comments/1013uxm/comment/j2loh3e/
        // The approach is as follows; Because every minute we can build at maximum one robot we can step from robot
        // build to robot build, instead of from minute to minute. So for every iteration - item on the queue - I
        // iterate over the robots and calculate how much time it will take in the current configuration to accumulate
        // enough resources to build that robot. This was still not fast enough, but the same Reddit post gave extra
        // options about reducing the search paths; If we cannot build the robot before the time limit ends, we don't
        // need to explore that possibility, and if we already have enough resources to build any robot, we don't need
        // to build a robot either. That last optimization was any idea I got from bartvanraaij.

        // Read input
        var blueprints = input
            .Select(line =>
            {
                var match = Regex.Match(line,
                    @"^Blueprint (?<id>[0-9]+): Each ore robot costs (?<ore>[0-9]+) ore. Each clay robot costs (?<clay>[0-9]+) ore. Each obsidian robot costs (?<obsidian_ore>[0-9]+) ore and (?<obsidian_clay>[0-9]+) clay. Each geode robot costs (?<geode_ore>[0-9]+) ore and (?<geode_obsidian>[0-9]+) obsidian.$");

                return new Blueprint(
                    int.Parse(match.Groups["id"].Value),
                    int.Parse(match.Groups["ore"].Value),
                    int.Parse(match.Groups["clay"].Value),
                    int.Parse(match.Groups["obsidian_ore"].Value),
                    int.Parse(match.Groups["obsidian_clay"].Value),
                    int.Parse(match.Groups["geode_ore"].Value),
                    int.Parse(match.Groups["geode_obsidian"].Value)
                );
            })
            .ToArray();
        
        timer.Start();
        
        // Part 1
        int score = 0;
        foreach (var blueprint in blueprints)
        {
            var numberOfGeodes = GetMaximumNumberOfGeodes(blueprint, 24);
            var qualityLevel = blueprint.Id * numberOfGeodes;
            if (DEBUG) Console.WriteLine($"Maximum number of geodes for blueprint {blueprint.Id}: {numberOfGeodes} for quality level {qualityLevel}");
            score += qualityLevel;
        }

        timer.Stop();
        Console.WriteLine($"What do you get if you add up the quality level of all of the blueprints in your list? {score} ({timer.ElapsedMilliseconds}ms)");
        
        // Part 2
        timer.Start();
        int partTwo = 1;
        foreach (var blueprint in blueprints.Take(3))
        {
            if (DEBUG) Console.WriteLine($"Searching max geode for blueprint {blueprint.Id}");
            var numberOfGeodes = GetMaximumNumberOfGeodes(blueprint, 32);
            if (DEBUG) Console.WriteLine($"Maximum number of geodes for blueprint {blueprint.Id}: {numberOfGeodes}");
            partTwo *= numberOfGeodes;
        }
        timer.Stop();
        Console.WriteLine($"What do you get if you multiply these numbers together? {partTwo} ({timer.ElapsedMilliseconds}ms)");

        int GetMaximumNumberOfGeodes(Blueprint blueprint, int minutes)
        {
            // Attempt DFS
            Stack<(int minutes, int ore, int clay, int obsidian, int geode, int oreRobots, int clayRobots, int obsidianRobots, int geodeRobots, string actions)> stack = new ();
            stack.Push((minutes: 0, ore: 0, clay: 0, obsidian: 0, geode: 0, oreRobots: 1, clayRobots: 0, obsidianRobots: 0, geodeRobots: 0, actions: ""));

            int numberOfGeodes = 0;
            while (stack.Count > 0)
            {
                var current = stack.Pop();
                
                var minutesLeft = minutes - current.minutes;

                // Given the current number of geode robots and the number of minutes left we can make a projection of
                // the guaranteed number of geodes cracked. By setting the number of geodes we might be able to  discard
                // more paths in a later stage. This however will also ensure the correct answer when the last robot is
                // is not build in the second to last minute.
                var guaranteed = current.geode + (current.geodeRobots * (minutes - current.minutes));
                if (guaranteed > numberOfGeodes)
                {
                    if (DEBUG) Console.WriteLine($"Found a new projected maximum number of geode for blueprint {blueprint.Id} on minute {current.minutes}: {guaranteed}, {stack.Count} items left on stack");
                    numberOfGeodes = guaranteed;
                }
                
                if (current.geode > numberOfGeodes)
                {
                    if (DEBUG) Console.WriteLine($"Found a new maximum number of geode for blueprint {blueprint.Id} on minute {current.minutes}: {current.geode}, {stack.Count} items left on stack");
                    numberOfGeodes = current.geode;
                }
                
                if (current.minutes >= minutes)
                {
                    continue;
                }
                
                List<(string name, int oreRobots, int clayRobots, int obsidianRobots, int geodeRobots, int oreCost, int clayCost, int obsidianCost)> robots = new()
                {
                    (name: "geode", oreRobots: 0, clayRobots: 0, obsidianRobots: 0, geodeRobots: 1, oreCost: blueprint.GeodeRobotOreCost, clayCost: 0, obsidianCost: blueprint.GeodeRobotObsidianCost), // Geode
                    (name: "obsidian", oreRobots: 0, clayRobots: 0, obsidianRobots: 1, geodeRobots: 0, oreCost: blueprint.ObsidianRobotOreCost, clayCost: blueprint.ObsidianRobotClayCost, obsidianCost: 0), // Obsidian
                    (name: "clay", oreRobots: 0, clayRobots: 1, obsidianRobots: 0, geodeRobots: 0, oreCost: blueprint.ClayRobotOreCost, clayCost: 0, obsidianCost: 0), // Clay
                    (name: "ore", oreRobots: 1, clayRobots: 0, obsidianRobots: 0, geodeRobots: 0, oreCost: blueprint.OreRobotOreCost, clayCost: 0, obsidianCost: 0), // Ore
                };

                foreach (var robot in robots)
                {
                    // when can I build this robot?
                    var mins = TimeToBuildRobot(current, robot);
                    
                    if (mins == int.MaxValue)
                    {
                        // we cannot build it with current set up
                        continue;
                    }

                    if (mins > minutesLeft - 1)
                    {
                        // we cannot build this robot before the deadline
                        continue;
                    }

                    // Exclude building robots we already have enough resources for.
                    // I had to tweak this a bit; I started with + 1, but got a too low answer for part 2. I switched
                    // to num of robots, which did not limit the search paths enough. + 2 yields the correct answer,
                    // but I cannot explain why...
                    if (current.ore > blueprint.MaxOreForAnyRobot + 2 && robot.oreRobots > 0) continue;
                    if (current.clay > blueprint.MaxClayForAnyRobot + 2 && robot.clayRobots > 0) continue;
                    if (current.obsidian > blueprint.MaxObsidianForAnyRobot + 2 && robot.obsidianRobots > 0) continue;
                    
                    // Let's build a robot!
                    // We will build the robot in current.minutes + mins, but we will calculate for the next minute, 
                    // which is why the next state is for current.minutes + mins + 1.
                    var next = current with
                    {
                        minutes = current.minutes + mins + 1,
                        
                        ore = current.ore + (current.oreRobots * mins) - robot.oreCost + current.oreRobots,
                        oreRobots = current.oreRobots + robot.oreRobots,
                        
                        clay = current.clay + (current.clayRobots * mins) - robot.clayCost + current.clayRobots,
                        clayRobots = current.clayRobots + robot.clayRobots,
                        
                        obsidian = current.obsidian + (current.obsidianRobots * mins) - robot.obsidianCost + current.obsidianRobots,
                        obsidianRobots = current.obsidianRobots + robot.obsidianRobots,
                        
                        geode = current.geode + (current.geodeRobots * mins) + current.geodeRobots,
                        geodeRobots = current.geodeRobots + robot.geodeRobots,
                        
                        actions = (current.actions + "\n" + $"Build robot to mine {robot.name} at minute {current.minutes + mins + 1}").Trim()
                    };

                    stack.Push(next);
                }
            }
            
            return numberOfGeodes;
        }

        int TimeToBuildRobot(
            (int minutes, int ore, int clay, int obsidian, int geode, int oreRobots, int clayRobots, int obsidianRobots, int geodeRobots, string actions) current, 
            (string name, int oreRobots, int clayRobots, int obsidianRobots, int geodeRobots, int oreCost, int clayCost, int obsidianCost) robot
        )
        {
            if (current.ore >= robot.oreCost && 
                current.clay >= robot.clayCost &&
                current.obsidian >= robot.obsidianCost)
            {
                return 0; // we can build it now!
            }

            if (robot.oreCost > 0 && robot.oreCost > current.ore && current.oreRobots == 0)
            {
                // We need ore, but don't have enough ore, not any ore producing robots, so we cannot build this robot
                return int.MaxValue; // as a substitute for infinity
            }
            
            if (robot.clayCost > 0 && robot.clayCost > current.clay && current.clayRobots == 0)
            {
                // We need clay, but don't have enough clay, not any clay producing robots, so we cannot build this robot
                return int.MaxValue; // as a substitute for infinity
            }
            
            if (robot.obsidianCost > 0 && robot.obsidianCost > current.obsidian && current.obsidianRobots == 0)
            {
                // We need obsidian, but don't have enough obsidian, not any obsidian producing robots, so we cannot build this robot
                return int.MaxValue; // as a substitute for infinity
            }
            
            // Okay, there is a moment somewhere in the future where we can build this robot.
            int mins = 0;
            if (robot.oreCost > 0 && robot.oreCost > current.ore) mins = Math.Max(mins, (int)Math.Ceiling((robot.oreCost - current.ore) / (double)current.oreRobots));
            if (robot.clayCost > 0 && robot.clayCost > current.clay) mins = Math.Max(mins, (int)Math.Ceiling((robot.clayCost - current.clay) / (double)current.clayRobots));
            if (robot.obsidianCost > 0 && robot.obsidianCost > current.obsidian) mins = Math.Max(mins, (int)Math.Ceiling((robot.obsidianCost - current.obsidian) / (double)current.obsidianRobots));

            return mins;
        }
    }

    class Blueprint
    {
        public int Id { get; }
        public int OreRobotOreCost { get; }
        public int ClayRobotOreCost { get; }
        public int ObsidianRobotOreCost { get; }
        public int ObsidianRobotClayCost { get; }
        public int GeodeRobotOreCost { get; }
        public int GeodeRobotObsidianCost { get; }

        public int MaxOreForAnyRobot { get; }
        public int MaxClayForAnyRobot { get; }
        public int MaxObsidianForAnyRobot { get; }
        
        public Blueprint(
            int id, 
            int oreRobotOreCost, 
            int clayRobotOreCost,
            int obsidianRobotOreCost,
            int obsidianRobotClayCost,
            int geodeRobotOreCost,
            int geodeRobotObsidianCost
        ) {
            Id = id;
            OreRobotOreCost = oreRobotOreCost;
            ClayRobotOreCost = clayRobotOreCost;
            ObsidianRobotOreCost = obsidianRobotOreCost;
            ObsidianRobotClayCost = obsidianRobotClayCost;
            GeodeRobotOreCost = geodeRobotOreCost;
            GeodeRobotObsidianCost = geodeRobotObsidianCost;

            // Determine the maximum amounts of every resource needed to build any other robot - thank you for the idea
            // bartvanraaij. We can use this to make a decision NOT build a certain robot if we have enough resources
            // of that robot to build any other robot.
            MaxOreForAnyRobot = Math.Max(Math.Max(Math.Max(OreRobotOreCost, ClayRobotOreCost), ObsidianRobotOreCost), GeodeRobotOreCost);
            MaxClayForAnyRobot = ObsidianRobotClayCost;
            MaxObsidianForAnyRobot = GeodeRobotObsidianCost;
        }
    }
}
