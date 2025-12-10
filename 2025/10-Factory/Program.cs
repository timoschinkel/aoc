using _10_Factory;
using Shared;

string[] input = File.ReadAllLines(Environment.GetEnvironmentVariable("INPUT") != null ? $"./{Environment.GetEnvironmentVariable("INPUT")}" : "./input.txt");

// Parse input
var machines = input.Select(Input.FromString).ToArray();

// Part 01
var timer = new AocTimer();
// perform calculation
var one = PartOne(machines);
timer.Duration($"What is the fewest button presses required to correctly configure the indicator lights on all of the machines? {one}");

// Part 02
timer = new AocTimer();
// perform calculation
timer.Duration($"The answer is: {0}");

long PartOne(Input[] inputs)
{
    long sum = 0;
    foreach (var i in inputs)
    {
        sum += MinimalPresses(i);
    }
    return sum;
}

long MinimalPresses(Input i)
{
    // We will perform a BFS to find the shortest path to our target 
    var target = i.Target;

    Queue<string> queue = new Queue<string>();
    Dictionary<string, long> presses = new Dictionary<string, long>();
    
    queue.Enqueue(new string('.', target.Length));
    presses.Add(new string('.', target.Length), 0);

    while (queue.Count > 0)
    {
        var current = queue.Dequeue();
        var count = presses[current];

        if (current == target)
        {
            return count;
        }

        foreach (var buttons in i.Buttons)
        {
            var next = Press(current, buttons);
            if (presses.GetValueOrDefault(next, long.MaxValue) > count + 1)
            {   // shorter route found
                presses[next] = count + 1;
                if (next == target)
                {   // early exit
                    return count + 1;
                }
                if (!queue.Contains(next))
                {
                    queue.Enqueue(next);
                }
            } 
        }
    }
    
    return 0;
}

string Press(string current, int[] buttons)
{
    string next = "";
    for (int i = 0; i < current.Length; i++)
    {
        next += buttons.Contains(i) ? (current[i] == '.' ? '#' : '.') : current[i];
    }
    return next;
}