using System.Diagnostics;
using System.Reflection;

namespace Day21;

class Program
{
    public static void Main(string[] args)
    {
        bool DEBUG = args.Contains("debug");
        bool EXAMPLE = args.Contains("example");

        string[] input = EXAMPLE
            ? File.ReadAllLines($"{Path.GetDirectoryName(Assembly.GetExecutingAssembly().Location)}/example.txt")
            : File.ReadAllLines($"{Path.GetDirectoryName(Assembly.GetExecutingAssembly().Location)}/input.txt");

        // Part 1; I don't know if this will bite me in the ass for part 2, but this was in my opinion the easiest
        // puzzle of this year so far. I use recursion to find the outcome for root; Every step I check the monkey's
        // operation. If it is a mathematical operation I perform it using the outcome of Resolve() on both sides of 
        // the equation. This will result in an answer pretty fast.
        
        // Read input
        Dictionary<string, string> monkeys = input
            .Select(line => line.Split(": "))
            .ToDictionary(parts => parts[0], parts => parts[1]);

        var timer = new Stopwatch();
        timer.Start();
        var score = Resolve("root");
        timer.Stop();
        Console.WriteLine($"What number will the monkey named root yell? {score} ({timer.ElapsedMilliseconds}ms)");

        long Resolve(string monkey)
        {
            var yell = monkeys[monkey];
            if (yell.Contains("+"))
            {
                var ms = yell.Split(" + ");
                return Resolve(ms[0]) + Resolve(ms[1]);
            }
            if (yell.Contains("-"))
            {
                var ms = yell.Split(" - ");
                return Resolve(ms[0]) - Resolve(ms[1]);
            }
            if (yell.Contains("*"))
            {
                var ms = yell.Split(" * ");
                return Resolve(ms[0]) * Resolve(ms[1]);
            } 
            if (yell.Contains("/"))
            {
                var ms = yell.Split(" / ");
                return Resolve(ms[0]) / Resolve(ms[1]);
            }
            
            return long.Parse(yell);
        }
    }
}