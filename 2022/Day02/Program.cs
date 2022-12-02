string[] input = File.ReadAllLines(@"./input.txt");

// Rock: A - X
// Paper: B - Y
// Scissor: C - Z

var scores = new Dictionary<string, int>
{
    {"A X", 4}, {"A Y", 8}, {"A Z", 3},
    {"B X", 1}, {"B Y", 5}, {"B Z", 9},
    {"C X", 7}, {"C Y", 2}, {"C Z", 6},
};


int score = 0;
foreach (var line in input)
{
    var hands = line.Split(" ");
    score += scores[line];
}

Console.WriteLine($"What would your total score be if everything goes exactly according to your strategy guide? {score}");