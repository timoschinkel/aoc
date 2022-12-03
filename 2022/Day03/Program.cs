string[] input = File.ReadAllLines(@"./input.txt");

var score = 0;
foreach (var line in input)
{
    var length = line.Length;
    var one = line.Substring(0, length / 2);
    var two = line.Substring(length / 2);

    int c = one.ToCharArray().Intersect(two.ToCharArray()).ToArray()[0];
    score += c >= 97 ? /* lowercase */ c - 96 : /* uppercase */ c - 38;
}

Console.WriteLine($"What is the sum of the priorities of those item types? {score}");