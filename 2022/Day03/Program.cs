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

var score2 = 0;
for (var i = 0; i < input.Length; i += 3)
{
    int intersect =
        input[i].ToCharArray().Intersect(input[i + 1].ToCharArray()).ToArray().Intersect(input[i + 2].ToCharArray()).ToArray()[0];
    
    score2 += intersect >= 97 ? /* lowercase */ intersect - 96 : /* uppercase */ intersect - 38;
}

Console.WriteLine($"What is the sum of the priorities of those item types? {score2}");