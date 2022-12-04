using System.Text.RegularExpressions;

string[] input = File.ReadAllLines(@"./input.txt");

var score = 0;
var scoreTwo = 0;

Regex rx = new Regex(@"^([0-9]+)-([0-9]+),([0-9]+)-([0-9]+)$");

foreach (var line in input)
{
    var match = rx.Matches(line).First();
    var one = Enumerable.Range(Int32.Parse(match.Groups[1].Value), Int32.Parse(match.Groups[2].Value) - Int32.Parse(match.Groups[1].Value) + 1);
    var two = Enumerable.Range(Int32.Parse(match.Groups[3].Value), Int32.Parse(match.Groups[4].Value) - Int32.Parse(match.Groups[3].Value) + 1);

    if (one.Except(two).Count() < one.Count() || two.Except(one).Count() < two.Count())
    {
        if (one.Except(two).Count() == 0 || two.Except(one).Count() == 0)
        {
            score++;
        }

        scoreTwo++;
    }
}

Console.WriteLine($"In how many assignment pairs does one range fully contain the other? {score}");
Console.WriteLine($"In how many assignment pairs do the ranges overlap? {scoreTwo}");