using System.Reflection;
using Shared;

string path = Path.GetDirectoryName(Assembly.GetExecutingAssembly().Location) ?? ".";
string[] input = File.ReadAllLines(Environment.GetEnvironmentVariable("INPUT") != null ? $"{path}/{Environment.GetEnvironmentVariable("INPUT")}" : $"{path}/input.txt");

// Parse input
Dictionary<string, List<string>> graph = new ();
foreach (var line in input)
{
    string[] parts = line.Split(' ');
    graph[parts[0].Substring(0, parts[0].Length - 1)] = parts.Skip(1).ToList();
}

// Part 01
var timer = new AocTimer();
// perform calculation
var one = PartOne(graph);
timer.Duration($"How many different paths lead from you to out? {one}");

// graph = new Dictionary<string, List<string>>
// {
//     {"svr", ["aaa", "bbb"]},
//     {"aaa", ["hhh", "fft"]},
//     {"bbb", ["iii", "fft", "jjj"]},
//     {"fft", ["ccc", "eee"]},
//     {"hhh", ["ccc"]},
//     {"iii", ["ggg"]},
//     {"ccc", ["dac"]},
//     {"eee", ["dac"]},
//     {"dac", ["fff", "ggg"]},
//     {"ggg", ["out"]},
//     {"fff", ["out"]},
//     {"jjj", ["dac"]}
// };

// Part 02
timer = new AocTimer();
// perform calculation
var two = PartTwo(graph);
timer.Duration($"How many of those paths visit both dac and fft? {two}");

long PartOne(Dictionary<string, List<string>> graph)
{
    return CountPaths(graph, "you", "out");
}

long PartTwo(Dictionary<string, List<string>> graph)
{
    /*
     * We have seen in part 1 that there are no cycles. We can use that knowledge.
     * Every path we are looking for paths that pass both ttf and dac **in any order**. So if we start seeking paths
     * from svr to either ttf or dac we should be able to find we encounter first. Then we can find all the paths
     * between ttf and dac - the order we need to search comes from the previous step. And finally we need to find
     * all paths between ttf/dac and out. We can multiply those numbers to find the number of permutations, and thus
     * the total number of paths.
     */
    
    Dictionary<string, List<string>> parents = new ();
    foreach (var node in graph.Keys)
    {
        var children = graph[node];
        foreach (var child in children)
        {
            if (!parents.ContainsKey(child)) parents[child] = [];
            parents[child].Add(node);
        }
    }
    
    /*
     * Let's assume the following simplified graph. If we try to perform a standards BFS we will "overshoot" the target
     * nodes - e.g. fft -, but we have no way of telling when we overshoot. Calculating all routes between svr and out
     * is also out of the question, as this will result in way too many routes. So we need to reduce the search space.
     * 
     *         ---- hhh ----
     *        |             |
     *   --- aaa ---   --- ccc ---   --- fff --- 
     *  |           | |           | |           |
     * svr          fft           dac          out
     *  |           | |           |||           |
     *   --- bbb ---   --- eee --- | --- ggg ---
     *        ||_______ jjj _______|      |
     *        |                           |
     *         ----------- iii -----------
     *
     *  |----- A -----|
     *               |----- B -----| 
     *                            |----- C -----|
     *
     * When we have each of these subgraphs, then we can perform a BFS on each of them,
     * and then we multiply these answers.
     */

    var first = "fft";
    var second = "dac";
    Dictionary<string, List<string>> A = new();
    try
    {
        A = BackTrackFrom(parents, "fft", "svr", ["dac"]);
    }
    catch (Exception)
    {
        // found forbidden item
        A = BackTrackFrom(parents, "dac", "svr");
        first = "dac";
        second = "fft";
    }
    
    // Console.WriteLine($"Size: {A.Count}");
    // Console.WriteLine($"Paths: {CountPaths(A, "svr", first)}");
    var pathsInA = CountPaths(A, "svr", first);

    List<string> visited = new();
    foreach (var node in A)
    {
        visited.Add(node.Key);
        foreach (var destination in graph[node.Key])
        {
            if (destination != first)
            {
                visited.Add(destination);
            }
        }
    }
    
    Dictionary<string, List<string>> B = BackTrackFrom(parents, second, first, null, visited);
    // Console.WriteLine($"Size: {B.Count}");
    // Console.WriteLine($"Paths: {CountPaths(B, first, second)}");
    var pathsInB = CountPaths(B, first, second);
    
    visited.Add(first);
    foreach (var node in B)
    {
        visited.Add(node.Key);
        foreach (var destination in graph[node.Key])
        {
            if (destination != second)
            {
                visited.Add(destination);
            }
        }
    }
    
    var C = BackTrackFrom(parents, "out", second, null, visited);
    // Console.WriteLine($"Size: {C.Count}");
    // Console.WriteLine($"Paths: {CountPaths(C, second, "out")}");
    var pathsInC = CountPaths(C, second, "out");
    
    return pathsInA * pathsInB * pathsInC;
}

Dictionary<string, List<string>> BackTrackFrom(Dictionary<string, List<string>> parents, string start, string destination, List<string>? forbidden = null, List<string>? excluded = null)
{
    forbidden ??= [];
    excluded ??= [];
    
    var graph = new Dictionary<string, List<string>>();
    
    Queue<string> queue = new ();
    queue.Enqueue(start);

    while (queue.Count > 0)
    {
        var current = queue.Dequeue();
        foreach (var parent in parents.GetValueOrDefault(current, new()))
        {
            if (forbidden.Contains(parent))
            {
                throw new Exception($"Found forbidden node {parent}");
            }

            if (excluded.Contains(parent)) continue;
            
            if (!graph.ContainsKey(parent)) graph[parent] = [];
            if (!graph[parent].Contains(current)) graph[parent].Add(current);

            if (parent == destination || parent == "svr") continue;
            
            if (!queue.Contains(parent)) queue.Enqueue(parent);
        }
    }
    
    return graph;
}

long CountPaths(Dictionary<string, List<string>> graph, string start, string end)
{
    var paths = new List<IEnumerable<string>>();
    
    // BFS with path 
    Queue<IEnumerable<string>> queue = new Queue<IEnumerable<string>>();
    queue.Enqueue(new List<string> { start });

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

            // if (destination == "out")
            // {
            //     continue;
            // }

            var newPath = path.Concat([destination]);
            if (destination == end)
            {
                paths.Add(newPath);
                continue;
            }
            
            queue.Enqueue(newPath);
        }
    }
    
    return paths.Count;
}