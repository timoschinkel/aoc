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
            var numberOfGeodes = GetMaximumNumberOfGeodes(blueprint);
            var qualityLevel = blueprint.Id * numberOfGeodes;
            if (DEBUG) Console.WriteLine($"Maximum number of geodes for blueprint {blueprint.Id}: {numberOfGeodes} for quality level {qualityLevel}");
            score += qualityLevel;
        }
        
        timer.Stop();
        Console.WriteLine($"What do you get if you add up the quality level of all of the blueprints in your list? {score} ({timer.ElapsedMilliseconds}ms)");

        int GetMaximumNumberOfGeodes(Blueprint blueprint)
        {
            // Attempt DFS
            Stack<(int minutes, int ore, int clay, int obsidian, int geode, int oreRobots, int clayRobots, int obsidianRobots, int geodeRobots, string actions)> stack = new ();
            stack.Push((minutes: 0, ore: 0, clay: 0, obsidian: 0, geode: 0, oreRobots: 1, clayRobots: 0, obsidianRobots: 0, geodeRobots: 0, actions: ""));

            int numberOfGeodes = 0;
            while (stack.Count > 0)
            {
                var current = stack.Pop();
                
                var minutesLeft = 25 - current.minutes;
                
                if (current.minutes >= 24)
                {
                    if (numberOfGeodes < current.geode)
                    {
                        if (DEBUG) Console.WriteLine($"Found a new maximum number of geode: {current.geode}, {stack.Count} items left on stack");
                        //if (DEBUG) Console.WriteLine(current.actions);
                        numberOfGeodes = current.geode;
                    }
                    continue;
                }
                
                // Determine the maximum amount of geode we can possibly get, given that we build a new geode robot
                // every minute from now on. That is a very broad condition, but for now it works.
                var projected = current.geode + BinomialCoefficient(current.geodeRobots + minutesLeft - 1);
                if (projected < numberOfGeodes)
                {
                    continue;
                }

                current = current with
                {
                    minutes = current.minutes + 1,
                    actions = (current.actions + "\n\n" + $"== Minute {current.minutes + 1} ==").Trim()
                };

                // check possible actions, favor creating geode robots. Because of the stack - as per the depth-first
                // approach - the prioritization is reversed.
                
                // Don't build any robot, just collect resources
                stack.Push(UpdateResources(current));
                
                // Test if we can build an Ore robot, but also check if we should build it; If we have enough ore to
                // build any other robot, we don't need to build another ore robot. This is due to the limitation of 
                // only being able to build one robot per minute.
                if (current.ore >= blueprint.OreRobotOreCost && current.ore < blueprint.MaxOreForAnyRobot + 1)
                {
                    // Build ore-collecting robot, then gather yields from robots, and ony after that add the new robot.
                    var c = current with
                    {
                        ore = current.ore - blueprint.OreRobotOreCost,
                        actions = current.actions + "\n" + $"Spend {blueprint.OreRobotOreCost} ore to start building a ore-collecting robot."
                    };
                    c = UpdateResources(c);
                    stack.Push(c with
                    {
                        oreRobots = c.oreRobots + 1,
                        actions = c.actions + "\n" + $"The new ore-collecting robot is ready; you now have {c.oreRobots + 1} of them."
                    });
                }
                
                // Test if we can build a Clay robot, but also check if we should build it; If we have enough clay to
                // build any other robot, we don't need to build another clay robot. This is due to the limitation of 
                // only being able to build one robot per minute.
                if (current.ore >= blueprint.ClayRobotOreCost && current.clay < blueprint.MaxClayForAnyRobot + 1)
                {
                    // Build clay-collecting robot, then gather yields from robots, and ony after that add the new robot.
                    var c = current with
                    {
                        ore = current.ore - blueprint.ClayRobotOreCost,
                        actions = current.actions + "\n" + $"Spend {blueprint.ClayRobotOreCost} ore to start building a clay-collecting robot."
                    };
                    c = UpdateResources(c);
                    stack.Push(c with
                    {
                        clayRobots = c.clayRobots + 1,
                        actions = c.actions + "\n" + $"The new clay-collecting robot is ready; you now have {c.clayRobots + 1} of them."
                    });
                }
                
                // Test if we can build an Obsidian robot, but also check if we should build it; If we have enough
                // obsidian to build any other robot, we don't need to build another obsidian robot. This is due to the
                // limitation of only being able to build one robot per minute.
                if (current.ore >= blueprint.ObsidianRobotOreCost && current.clay >= blueprint.ObsidianRobotClayCost && current.obsidian < blueprint.MaxObsidianForAnyRobot + 1)
                {
                    // Build obsidian-collecting robot, then gather yields from robots, and ony after that add the new robot.
                    var c = current with
                    {
                        ore = current.ore - blueprint.ObsidianRobotOreCost,
                        clay = current.clay - blueprint.ObsidianRobotClayCost,
                        actions = current.actions + "\n" + $"Spend {blueprint.ObsidianRobotOreCost} ore and {blueprint.ObsidianRobotClayCost} clay to start building an obsidian-collecting robot."
                    };
                    c = UpdateResources(c);
                    stack.Push(c with
                    {
                        obsidianRobots = c.obsidianRobots + 1,
                        actions = c.actions + "\n" + $"The new obsidian-collecting robot is ready; you now have {c.obsidianRobots + 1} of them."
                    });
                }
                
                // Test if we can build a Geode robot. We always want to build a geode robot if we can!
                if (current.ore >= blueprint.GeodeRobotOreCost && current.obsidian >= blueprint.GeodeRobotObsidianCost)
                {
                    // Build obsidian-collecting robot, then gather yields from robots, and ony after that add the new robot.
                    var c = current with
                    {
                        ore = current.ore - blueprint.GeodeRobotOreCost,
                        obsidian = current.obsidian - blueprint.GeodeRobotObsidianCost,
                        actions = current.actions + "\n" + $"Spend {blueprint.GeodeRobotOreCost} ore and {blueprint.GeodeRobotObsidianCost} obsidian to start building a geode-collecting robot."
                    };
                    c = UpdateResources(c);
                    stack.Push(c with
                    {
                        geodeRobots = c.geodeRobots + 1,
                        actions = c.actions + "\n" + $"The new geode-collecting robot is ready; you now have {c.geodeRobots + 1} of them."
                    });
                }

                (int minutes, int ore, int clay, int obsidian, int geode, int oreRobots, int clayRobots, int
                    obsidianRobots, int geodeRobots, string actions) UpdateResources(
                        (int minutes, int ore, int clay, int obsidian, int geode, int oreRobots, int clayRobots, int
                            obsidianRobots, int geodeRobots, string actions) state)
                {
                    if (state.oreRobots > 0)
                    {
                        state = state with
                        {
                            ore = state.ore + state.oreRobots,
                            actions = state.actions + "\n" + $"{state.oreRobots} ore-collecting robot(s) collects {state.oreRobots} ore; you now have {state.ore + state.oreRobots} ore."
                        };
                    }
                    if (state.clayRobots > 0)
                    {
                        state = state with
                        {
                            clay = state.clay + state.clayRobots,
                            actions = state.actions + "\n" + $"{state.clayRobots} clay-collecting robot(s) collects {state.clayRobots} clay; you now have {state.clay + state.clayRobots} clay."
                        };
                    }
                    if (state.obsidianRobots > 0)
                    {
                        state = state with
                        {
                            obsidian = state.obsidian + state.obsidianRobots,
                            actions = state.actions + "\n" + $"{state.obsidianRobots} obsidian-collecting robot(s) collects {state.obsidianRobots} obsidian; you now have {state.obsidian + state.obsidianRobots} obsidian."
                        };
                    }
                    if (state.geodeRobots > 0)
                    {
                        state = state with
                        {
                            geode = state.geode + state.geodeRobots,
                            actions = state.actions + "\n" + $"{state.geodeRobots} geode-collecting robot(s) collects {state.geodeRobots} geode; you now have {state.geode + state.geodeRobots} geode."
                        };
                    }

                    return state;
                }
            }
            
            return numberOfGeodes;
        }

        int BinomialCoefficient(int n)
        {
            return (n * (n + 1)) / 2;
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
