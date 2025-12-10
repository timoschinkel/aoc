using System.Text.RegularExpressions;
using Shared;
using Range = _02_Gift_Shop.Range;
using System.Reflection;

string path = Path.GetDirectoryName(Assembly.GetExecutingAssembly().Location) ?? ".";
string[] input = File.ReadAllLines(Environment.GetEnvironmentVariable("INPUT") != null ? $"{path}/{Environment.GetEnvironmentVariable("INPUT")}" : $"{path}/input.txt");

// Parse input
_02_Gift_Shop.Range[] ranges = input[0].Split(",").Select(_02_Gift_Shop.Range.FromInput).ToArray();

// Part 01
var timer = new AocTimer();
// perform calculation
var one = PartOne(ranges);
timer.Duration($"What do you get if you add up all of the invalid IDs? {one}");

// ranges = new[]
// {
//     new Range(11, 22)
// };

// Part 02
timer = new AocTimer();
// perform calculation
var two = PartTwo(ranges);
timer.Duration($"What do you get if you add up all of the invalid IDs using these new rules? {two}");

timer = new AocTimer();
// perform calculation
two = PartTwoOptimized(ranges);
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

long PartTwoOptimized(Range[] ranges)
{
    long sum = 0;
    foreach (var range in ranges)
    {
        for (var i = range.First; i <= range.Last; i++)
        {
            if (HasPattern(i))
            {
                sum += i;
            }
        }
    }
    return sum;
}

bool HasPattern(long n)
{
    var s = n.ToString();
    int length = s.Length;

    for (int patternLength = 1; patternLength <= (int)Math.Floor(length / 2.0); patternLength++)
    {
        if (HasPatternOfLength(n, s, patternLength))
        {
            return true;
        }
    }
    
    return false;
}

bool HasPatternOfLength(long n, string s, int patternLength)
{
    if (s.Length % patternLength != 0) return false; // we can never have a pattern
        
    string pattern = s.Substring(0, patternLength);
    for (int i = 1; i < s.Length / patternLength; i++)
    {
        if (s.Substring(i * patternLength, patternLength) != pattern)
        {
            return false;
        }
    }

    return true;
}

bool HasPatternOfLengthUsingPurelyNumericOperations(long n, string s, int patternLength)
{
    /*
     * In my mind string operations are expensive, so why not leave the string comparisons and check the patterns purely
     * using math. Given the following input n=123123 we can find at most 3 patterns; length 1, length 2 and length 3.
     *
     * Looking at the pattern of length 2 we have the parts 12 31 12. We can get the final part by performing
     * n % 10^{pattern length}, so 123123 % 10^2. The following groups can be found by iterating over the number of
     * patterns (number of digits / pattern length) and for every group perform the following operation:
     * ⌊n / 10^{i * pattern length}⌋ % 10^{pattern length}
     *
     * But! This actually is not faster than the optimized solution that uses string patterns...
     */
    if (s.Length % patternLength != 0) return false; // we can never have a pattern
        
    var pattern = (long)(n % Math.Pow(10, patternLength));
    
    for (int i = 1; i < s.Length / patternLength; i++)
    {
        var compare = (long)(Math.Floor(n / Math.Pow(10, i * patternLength)) % Math.Pow(10, patternLength));
        if (compare != pattern) return false;
    }

    return true;
}