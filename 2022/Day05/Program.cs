using System.Text.RegularExpressions;

string[] input = File.ReadAllLines(@"./input.txt");

/*
    [D]    
[N] [C]    
[Z] [M] [P]
 1   2   3 
 */
Dictionary<int, Stack<char>> example = new Dictionary<int, Stack<char>>
{
    { 1, new Stack<char>(new char[]{'Z', 'N'}) }, 
    { 2, new Stack<char>(new char[]{'M', 'C', 'D'}) }, 
    { 3, new Stack<char>(new char[]{'P'}) }, 
};

/*
[V]         [T]         [J]        
[Q]         [M] [P]     [Q]     [J]
[W] [B]     [N] [Q]     [C]     [T]
[M] [C]     [F] [N]     [G] [W] [G]
[B] [W] [J] [H] [L]     [R] [B] [C]
[N] [R] [R] [W] [W] [W] [D] [N] [F]
[Z] [Z] [Q] [S] [F] [P] [B] [Q] [L]
[C] [H] [F] [Z] [G] [L] [V] [Z] [H]
 1   2   3   4   5   6   7   8   9 
*/

Dictionary<int, Stack<char>> assignment = new Dictionary<int, Stack<char>>
{
    { 1, new Stack<char>(new char[]{'C', 'Z', 'N', 'B', 'M', 'W', 'Q', 'V'}) }, 
    { 2, new Stack<char>(new char[]{'H', 'Z', 'R', 'W', 'C', 'B'}) }, 
    { 3, new Stack<char>(new char[]{'H', 'Z', 'R', 'W', 'C', 'B'}) }, 
    { 4, new Stack<char>(new char[]{'Z', 'S', 'W', 'H', 'F', 'N', 'M', 'T'}) }, 
    { 5, new Stack<char>(new char[]{'G', 'F', 'W', 'L', 'N', 'Q', 'P'}) }, 
    { 6, new Stack<char>(new char[]{'L', 'P', 'W'}) }, 
    { 7, new Stack<char>(new char[]{'V', 'B', 'D', 'R', 'G', 'C', 'Q', 'J'}) }, 
    { 8, new Stack<char>(new char[]{'Z', 'Q', 'N', 'B', 'W'}) }, 
    { 9, new Stack<char>(new char[]{'H', 'L', 'F', 'C', 'G', 'T', 'J'}) }, 
};

Regex rx = new Regex(@"^move ([0-9]+) from ([0-9]+) to ([0-9]+)$");

var stack = assignment;

foreach (var line in input)
{
    if (line.StartsWith("move") == false) continue; // these are not the droids you're looking for
    
    var match = rx.Match(line);
    var amount = Int32.Parse(match.Groups[1].Value);
    var source = Int32.Parse(match.Groups[2].Value);
    var destination = Int32.Parse(match.Groups[3].Value);

    for (var i = 0; i < amount; i++)
    {
        stack[destination].Push(stack[source].Pop());
    }
}

string partOne = "";
foreach (var entry in stack)
{
    partOne += entry.Value.Peek();
}

Console.WriteLine($"After the rearrangement procedure completes, what crate ends up on top of each stack? {partOne}");

void DebugPrint(Dictionary<int, Stack<char>> s)
{
    foreach (var entry in s)
    {
        Console.WriteLine($"{entry.Key} - {new string(entry.Value.Reverse().ToArray())}");
    }
    Console.WriteLine("");
}