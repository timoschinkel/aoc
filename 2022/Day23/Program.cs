using System.Diagnostics;
using System.Reflection;

namespace Day23;

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

        Dictionary<(int x, int y), char> elves = new();
        
        // Part 1; The difficulty in this assignment in my opinion lies in the administration. How to properly start at
        // at a different direction for every subsequent step. My approach is to use the step counter as an indicator. I
        // resorted to some rather ugly nested set of if-statements, but it works.
        
        // Read input
        for (int y = 0; y < input.Length; y++)
        {
            for (int x = 0; x < input[y].Length; x++)
            {
                if (input[y][x] == '#')
                {
                    elves[(x, y)] = input[y][x];
                }
            }
        }

        Print();
        
        timer.Start();
        
        for (int round = 0; round < 10; round++)
        {
            Dictionary<(int x, int y), (int x, int y)> proposals = new();
            Dictionary<(int x, int y), int> proposalCounts = new();

            // First half
            foreach (var entry in elves)
            {
                var elf = entry.Key;
                var neighbors = GetNeighbors(elf);

                if (neighbors.Count == 0)
                {
                    //if (DEBUG) Console.WriteLine($"Elf on ({elf.x}, {elf.y}) has no neighbors, so it will not move");
                    continue;
                }
                
                //if (DEBUG) Console.WriteLine($"Fetching proposals from elf on ({elf.x}, {elf.y})");

                var proposal = elf;
                for (int pIndex = 0; pIndex < 4; pIndex++)
                {
                    if ((pIndex + round) % 4 == 0)
                    {
                        //if (DEBUG) Console.WriteLine("Check NORTH");
                        // north
                        if (!elves.ContainsKey((x: elf.x - 1, y: elf.y - 1)) && !elves.ContainsKey(elf with { y = elf.y - 1 }) && !elves.ContainsKey((x: elf.x + 1, y: elf.y - 1)))
                        {   // North
                            //if (DEBUG) Console.WriteLine("Propose moving NORTH");
                            proposal = elf with { y = elf.y - 1 };
                            break;
                        }
                    } 
                    else if ((pIndex + round) % 4 == 1)
                    {   // south
                        //if (DEBUG) Console.WriteLine("Check SOUTH");
                        if (!elves.ContainsKey((x: elf.x - 1, y: elf.y + 1)) && !elves.ContainsKey(elf with { y = elf.y + 1 }) && !elves.ContainsKey((x: elf.x + 1, y: elf.y + 1)))
                        {   // South
                            //if (DEBUG) Console.WriteLine("Propose moving SOUTH");
                            proposal = elf with { y = elf.y + 1 };
                            break;
                        }
                    }
                    else if ((pIndex + round) % 4 == 2)
                    {   // west
                        //if (DEBUG) Console.WriteLine("Check WEST");
                        if (!elves.ContainsKey((x: elf.x - 1, y: elf.y - 1)) && !elves.ContainsKey(elf with { x = elf.x - 1 }) && !elves.ContainsKey((x: elf.x - 1, y: elf.y + 1)))
                        {   // West
                            //if (DEBUG) Console.WriteLine("Propose moving WEST");
                            proposal = elf with { x = elf.x - 1 };
                            break;
                        }
                    }
                    else if ((pIndex + round) % 4 == 3)
                    {   // east
                        //if (DEBUG) Console.WriteLine("Check EAST");
                        if (!elves.ContainsKey((x: elf.x + 1, y: elf.y - 1)) && !elves.ContainsKey(elf with { x = elf.x + 1 }) && !elves.ContainsKey((x: elf.x + 1, y: elf.y + 1)))
                        {   // East
                            //if (DEBUG) Console.WriteLine("Propose moving EAST");
                            proposal = elf with { x = elf.x + 1 };
                            break;
                        }
                    }
                }

                if (proposal == elf)
                {
                    //if (DEBUG) Console.WriteLine("Elf could not find a suitable proposal, remaining...");
                    continue;
                }

                proposalCounts[proposal] = proposalCounts.GetValueOrDefault(proposal, 0) + 1;
                proposals[elf] = proposal;
            }

            if (proposals.Count == 0)
            {
                // No elves move, we might as well stop
                if (DEBUG) Console.WriteLine("No elf moved, stopping early...");
                Print();
                break;
            }
            
            // Second half
            foreach (var entry in proposals)
            {
                var elf = entry.Key;
                var proposal = entry.Value;

                if (proposalCounts[proposal] > 1)
                {   // None of the proposing elves move
                    continue;
                }
                
                // Move elf from current to proposed position
                elves.Remove(elf);
                elves[proposal] = '#';
            }

            Print();
        }
        
        Print(true);
        
        // Find bounding boxes
        int maxx = Int32.MinValue, minx = Int32.MaxValue, maxy = Int32.MinValue, miny = Int32.MaxValue;
        foreach (var entry in elves)
        {
            maxx = Math.Max(maxx, entry.Key.x);
            minx = Math.Min(minx, entry.Key.x);
            maxy = Math.Max(maxy, entry.Key.y);
            miny = Math.Min(miny, entry.Key.y);
        }
        
        var region = (maxx - minx + 1) * (maxy - miny + 1);
        var numOfEmpty = region - elves.Count;
        timer.Stop();
        
        Console.WriteLine($"How many empty ground tiles does that rectangle contain? {numOfEmpty} ({timer.ElapsedMilliseconds}ms)");
        
        List<(int x, int y)> GetNeighbors((int x, int y) position)
        {
            var neighbors = new List<(int x, int y)>();
            
            List<(int x, int y)> deltas = new()
            {
                (x: -1, y: -1), // NW
                (x:  0, y: -1), // N
                (x: +1, y: -1), // NE
                (x: +1, y:  0), // E
                (x: +1, y: +1), // SE
                (x:  0, y: +1), // S
                (x: -1, y: +1), // SW
                (x: -1, y:  0), // W
            };

            foreach (var delta in deltas)
            {
                var prospect = (x: position.x + delta.x, y: position.y + delta.y);
                if (elves.ContainsKey(prospect))
                {
                    neighbors.Add(prospect);
                }
            }

            return neighbors;
        }

        void Print(bool bounding = false)
        {
            if (!DEBUG) return;

            int maxx = Int32.MinValue, minx = Int32.MaxValue, maxy = Int32.MinValue, miny = Int32.MaxValue;
            if (bounding == false)
            {
                maxx = 14;
                minx = -3;
                maxy = 12;
                miny = -2;
            }
            else
            {
                foreach (var entry in elves)
                {
                    maxx = Math.Max(maxx, entry.Key.x);
                    minx = Math.Min(minx, entry.Key.x);
                    maxy = Math.Max(maxy, entry.Key.y);
                    miny = Math.Min(miny, entry.Key.y);
                }
            }
            
            //for (int row = Math.Min(0, miny - 1); row <= maxy + 1; row++)
            for (int row = miny; row <= maxy; row++)
            {
                // for (int col = Math.Min(0, minx - 1); col <= maxx + 1; col++)
                for (int col = minx; col <= maxx; col++)
                {
                    if (row == 0 && col == 0)
                    {
                        Console.ForegroundColor = ConsoleColor.Magenta;
                    }
                    
                    if (elves.ContainsKey((x: col, y: row)))
                    {
                        Console.Write('#');
                    }
                    else
                    {
                        Console.Write('.');
                    }
                    
                    Console.ResetColor();
                }
                Console.WriteLine("");
            }
            Console.WriteLine("");
        }
    }
}