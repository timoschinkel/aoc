string[] input = File.ReadAllLines(@"./input.txt");

// Rock: A & X = 1
// Paper: B & Y = 2
// Scissor: C & Z =3

var scores = new Dictionary<string, int>
{
    {"A X", 4}, {"A Y", 8}, {"A Z", 3},
    {"B X", 1}, {"B Y", 5}, {"B Z", 9},
    {"C X", 7}, {"C Y", 2}, {"C Z", 6},
};


int score = 0;
foreach (var line in input)
{
    score += scores[line];
}

Console.WriteLine($"What would your total score be if everything goes exactly according to your strategy guide? {score}");

// Rock: A & X = 1
// Paper: B & Y = 2
// Scissor: C & Z =3
//
// X: lose
// Y: draw
// Z: win

var scoresTwo = new Dictionary<string, int>
{
    {"A X", /* Z to lose: 3 + 0 */ 3},  
    {"A Y", /* X to draw: 1 + 3 */ 4},
    {"A Z", /* Y to win : 2 + 6 */ 8},

    {"B X", /* X to lose: 1 + 0 */ 1},
    {"B Y", /* Y to draw: 2 + 3 */ 5},
    {"B Z", /* Z to win : 3 + 6 */ 9},

    {"C X", /* Y to lose: 2 + 0 */ 2},
    {"C Y", /* Z to draw: 3 + 3 */ 6},
    {"C Z", /* X to win : 1 + 6 */ 7},
};

var scoreTwo = 0;

foreach (var line in input)
{
    scoreTwo += scoresTwo[line];
}

Console.WriteLine($"What would your total score be if everything goes exactly according to your strategy guide? {scoreTwo}");