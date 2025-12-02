using System.Diagnostics;
using System.Text.RegularExpressions;
using Range = _02_Gift_Shop.Range;

string[] input = File.ReadAllLines(Environment.GetEnvironmentVariable("INPUT") != null ? $"./{Environment.GetEnvironmentVariable("INPUT")}" : "./input.txt");

// Parse input
_02_Gift_Shop.Range[] ranges = input[0].Split(",").Select(_02_Gift_Shop.Range.FromInput).ToArray();

// Part 01
var sw = Stopwatch.StartNew();
// perform calculation
var one = PartOne(ranges);
sw.Stop();
PrintElapsedTime($"What do you get if you add up all of the invalid IDs? {one} ([ELAPSED])", sw.Elapsed);

// Part 02
sw = Stopwatch.StartNew();
// perform calculation
sw.Stop();
PrintElapsedTime($"The answer is: {0} ([ELAPSED])", sw.Elapsed);

long PartOne(Range[] ranges)
{
    long sum = 0;
    foreach (var range in ranges)
    {
        for (var i = range.First; i <= range.Last; i++)
        {
            var width = i.ToString().Length;
            if (width % 2 == 1) continue; // an uneven length cannot be split in two

            /*
             * We can perform string operations, but math calculations are generally faster.
             * Assuming 1234 then the first half is 1234/100, and the second half is 1234%100.
             * We can use this to perform a division and a modulo to verify if the first half is the same
             * as the second half.
             */
            long power = (long)Math.Pow(10, width / 2);
            if (i % power == i / power)
            {
                //Console.WriteLine($"Found invalid ID: {i}");
                sum += i;
            }
        }
    }
    return sum;
}

void PrintElapsedTime(string message, TimeSpan ts)
{
    var ns = ts.TotalNanoseconds;
    var parts = message.Split("[ELAPSED]");
    for (var i = 0; i < parts.Length; i++)
    {
        Console.Write(parts[i]);
        if (i < parts.Length - 1)
        {
            if (ns > 1000000000)
            {
                Console.ForegroundColor = ConsoleColor.Red;
                Console.Write($"{Math.Round(ns / 1000000000, 2)}s");
            }
            else if (ns > 1000000)
            {
                Console.ForegroundColor = ConsoleColor.Yellow;
                Console.Write($"{Math.Ceiling(ns / 1000000)}ms");
            }
            else if (ns > 1000)
            {
                Console.ForegroundColor = ConsoleColor.Green;
                Console.Write($"{Math.Ceiling(ns / 1000)}μs");
            }
            else
            {
                Console.ForegroundColor = ConsoleColor.Green;
                Console.Write($"{ns}ns");
            }
            
            Console.ResetColor();
        }
    }
    
    Console.WriteLine("");
}
