using System.Text.RegularExpressions;
using Shared;
using Range = _02_Gift_Shop.Range;

string[] input = File.ReadAllLines(Environment.GetEnvironmentVariable("INPUT") != null ? $"./{Environment.GetEnvironmentVariable("INPUT")}" : "./input.txt");

// Parse input
_02_Gift_Shop.Range[] ranges = input[0].Split(",").Select(_02_Gift_Shop.Range.FromInput).ToArray();

// Part 01
var timer = new AocTimer();
// perform calculation
var one = PartOne(ranges);
timer.Duration($"What do you get if you add up all of the invalid IDs? {one}");

// Part 02
timer = new AocTimer();
// perform calculation
var two = PartTwo(ranges);
timer.Duration($"What do you get if you add up all of the invalid IDs using these new rules? {two}");

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

long PartTwo(Range[] ranges)
{
    long sum = 0;
    foreach (var range in ranges)
    {
        for (var i = range.First; i <= range.Last; i++)
        {
            /*
             * This is a very in efficient solution, especially for day 02 - 820ms on my machine.
             * But! It works and it is concise.
             * This regular expression matches strings that have a repeating pattern that covers the entire "number"
             */
            if (Regex.IsMatch(i.ToString(), @"^(?<pattern>[0-9]+)(\k<pattern>)+$"))
            {
                sum += i;
            }
        }
    }
    return sum;
}
