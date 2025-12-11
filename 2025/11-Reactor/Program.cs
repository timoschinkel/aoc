using System.Reflection;
using Shared;

string path = Path.GetDirectoryName(Assembly.GetExecutingAssembly().Location) ?? ".";
string[] input = File.ReadAllLines(Environment.GetEnvironmentVariable("INPUT") != null ? $"{path}/{Environment.GetEnvironmentVariable("INPUT")}" : $"{path}/input.txt");

// Parse input
Dictionary<string, string[]> graph = new Dictionary<string, string[]>();
foreach (var line in input)
{
    string[] parts = line.Split(' ');
    graph[parts[0].Substring(0, parts[0].Length - 1)] = parts.Skip(1).ToArray();
}

// Part 01
var timer = new AocTimer();
// perform calculation
var one = PartOne(graph);
timer.Duration($"How many different paths lead from you to out? {one}");

// Part 02
timer = new AocTimer();
// perform calculation
timer.Duration($"The answer is: {0}");

long PartOne(Dictionary<string, string[]> graph)
{
    var paths = new List<IEnumerable<string>>();
    
    // BFS with path 
    Queue<IEnumerable<string>> queue = new Queue<IEnumerable<string>>();
    queue.Enqueue(new List<string> { "you" });

    while (queue.Count > 0)
    {
        var path = queue.Dequeue();
        var node = path.Last();

        foreach (var destination in graph[node])
        {
            if (path.Contains(destination))
            {
                throw new Exception("Cycle detected");
            }

            var newPath = path.Concat([destination]);
            if (destination == "out")
            {
                paths.Add(newPath);
                continue;
            }
            
            queue.Enqueue(newPath);
        }
    }
    
    return paths.Count;
}