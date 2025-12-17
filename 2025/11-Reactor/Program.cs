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

timer = new AocTimer();
// perform calculation
two = PartTwoOptimized(graph);
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
     *
     * Edit: A standard BFS is slow (more than one minute on my machine). Looking on Google there are two ways to solve
     * counting the number of paths in a directed acyclic graph; BFS with memoization and Dynamic Programming.
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
    //Console.WriteLine($"Paths svr - {first}: {CountPaths(A, "svr", first)}");
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
    //Console.WriteLine($"Paths {first} - {second}: {CountPaths(B, first, second)}");
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
    //Console.WriteLine($"Paths {second} - out: {CountPaths(C, second, "out")}");
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

long CountPathsWithBFS(Dictionary<string, List<string>> graph, string start, string end)
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

long CountPathsWithMemoization(Dictionary<string, List<string>> graph, string start, string end)
{
    Dictionary<string, long> memo = new ();

    long CountWithMemo(Dictionary<string, List<string>> graph, string node, string destination)
    {
        if (node == destination) return 1;
        if (memo.ContainsKey(node)) return memo[node];

        long paths = 0;
        foreach (var child in graph[node])
        {
            paths += CountPathsWithMemoization(graph, child, destination);
        }
        memo[node] = paths;
        return paths;
    }
    
    return CountWithMemo(graph, start, end);
}

long CountPaths(Dictionary<string, List<string>> graph, string start, string end)
{
    // Optimization 01: Use BFS with memoization
    return CountPathsWithMemoization(graph, start, end);
    
    // return CountPathsWithBFS(graph, start, end);
}

long PartTwoOptimized(Dictionary<string, List<string>> graph)
{
    /*
     * The solution for part 2 was the slowest solution for this AoC year. I started playing with the graph to see if
     * counting paths could be optimized. I also employed Google in my search. One solution from Google was to use
     * memoization in the BFS, and the other was to use [Dynamic Programming][1]. The solution Google gave me required
     * the nodes to be ordered topologically, and then walk backwards from the root. That required another operation
     * to order the nodes. I also considered simplifying the graph; if a vertex goes from A to B and a vertex goes from
     * B to C, we can remove the B and have a vertex from A directly to C. I did not further pursue this idea.
     *
     * However! I was browsing the Advent of Code subreddit and I came across a [post][2] by u/EverybodyLovesChaka that
     * effectively did just that; simplified the graph until only relevant vertices were left.
     *
     * In my own words; assuming the following graph
     *   ->- aaa ->- ->- ccc
     *  ^     |     v
     * svr    v    fft
     *  v     |     ^
     *   ->- bbb ->-
     *
     * svr has two children, and both "occur" once: svr: { aaa: 1, bbb: 1 } and aaa has three children:
     * aaa { ccc: 1, fft: 1, bbb: 1 }. We can remove aaa from this graph, by replace aaa with its contents:
     *
     * svr: { aaa: 1, bbb: 1 } <-- aaa: { ccc: 1, fft: 1, bbb: 1 }
     *
     * Becomes
     *
     * svr: { bbb: 1 + 1, ccc: 1, fft: 1 } --> svr: { bbb: 2, ccc: 1, fft: 1 }
     * 
     *   ->1- ccc
     *  |
     *  ^->1- fft 
     *  |
     *  ^->2- bbb
     *  |
     * svr
     *  v
     *   ->1- bbb ->1- fft
     *
     * The graph has been simplified - and is now more like a tree - into a number of paths. If we keep simplifying all
     * the children of svr that are not our end node (fft) then we end up with the number of paths from svr:
     *
     * svr: { bbb: 2, ccc: 1, fft: 1 }
     *
     * replace bbb:
     * svr: { bbb: 2 * ({ fft: 1 }), ccc: 1, fft: 1 } --> svr: { ccc: 1, fft: 2 * 1 + 1 } --> svr: { ccc: 1, fft: 3 }
     *
     * replace ccc - ccc is special as it does not have a path towards our end node, so no replacement is performed, but
     * it _is_ removed from the final output:
     * svr: { fft: 3 }
     * 
     * Now for this puzzle we have three potential end nodes - fft, dac and out -, and three potential start nodes -
     * svr, fft and dac. So we need to perform the process explained above for all three start nodes, and we should
     * simplify all nodes except for our end nodes.
     *
     * After that it is a matter of finding which comes first in the paths, fft or dac.
     * 
     * [1]: https://en.wikipedia.org/wiki/Dynamic_programming
     * [2]: https://www.reddit.com/r/adventofcode/comments/1pmuayw/2025_day_11_part_2_was_i_the_only_one_who_used/
     */
    
    Dictionary<string, Dictionary<string, long>> counts = new();
    foreach (var entry in graph)
    {
        counts[entry.Key] = new ();
        foreach (var destination in entry.Value)
        {
            counts[entry.Key][destination] = 1;
        }
    }

    foreach (var start in (string[])["svr", "fft", "dac"])
    {
        // var start = "svr";
        string[] end = ["fft", "dac", "out"];

        while (counts[start].Count(i => !end.Contains(i.Key)) > 0)
        {
            var a = counts[start].Keys.First((destination) => !end.Contains(destination));
            foreach (var entry in counts.Where(entry => entry.Value.ContainsKey(a)))
            {
                // Console.WriteLine($"{entry.Key}: {{{string.Join(", ", counts[entry.Key].Select((e) => $"{e.Key}: {e.Value}"))}}}");

                // replace a with counts[a]

                foreach (var destination in counts[a])
                {
                    if (counts[entry.Key].ContainsKey(destination.Key))
                    {
                        counts[entry.Key][destination.Key] += counts[entry.Key][a] * destination.Value;
                    }
                    else
                    {
                        counts[entry.Key].Add(destination.Key, counts[entry.Key][a] * destination.Value);
                    }
                }

                counts[entry.Key].Remove(a);

                //Console.WriteLine($"{entry.Key}: {{{string.Join(", ", counts[entry.Key].Select((e) => $"{e.Key}: {e.Value}"))}}}");
            }

            counts.Remove(a);
            //Console.WriteLine("----");
        }

        // Console.WriteLine($"{start}: {{{string.Join(", ", counts[start].Select((entry) => $"{entry.Key}: {entry.Value}"))}}}");
    }
    
    // Which comes first? fft or dac?
    var first = !counts["dac"].ContainsKey("fft") ? "fft" : "dac";
    var second = first == "fft" ? "dac" : "fft";

    return counts["svr"][first] * counts[first][second] * counts[second]["out"];
}