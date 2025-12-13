using _10_Factory;
using System.Reflection;
using Shared;

string path = Path.GetDirectoryName(Assembly.GetExecutingAssembly().Location) ?? ".";
string[] input = File.ReadAllLines(Environment.GetEnvironmentVariable("INPUT") != null ? $"{path}/{Environment.GetEnvironmentVariable("INPUT")}" : $"{path}/input.txt");

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
var two = PartTwo(machines);
timer.Duration($"What is the fewest button presses required to correctly configure the joltage level counters on all of the machines? {two}");

long PartOne(Input[] inputs)
{
    long sum = 0;
    foreach (var i in inputs)
    {
        sum += MinimalPressesToMatchIndicatorLights(i);
    }
    return sum;
}

long PartTwo(Input[] inputs)
{
    long sum = 0;
    for (int i = 0; i < inputs.Length; i++)
    {
        var p = MinimalPressesToMatchJoltageLevels(inputs[i]);
        // Console.WriteLine($"{string.Join(",", inputs[i].Joltage)}: {p} presses");
        sum += p;
    }
    return sum;
}

long MinimalPressesToMatchIndicatorLights(Input i)
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

long MinimalPressesToMatchJoltageLevels(Input i)
{
    /*
     * My math is not good enough that I can solve linear equations. Some people on Reddit have explained very nicely
     * how you can construct linear equations from the input and that you can solve Gaussian elimination to solve this
     * with free variables[1]. But I found another approach on Reddit that seems to suit my skills better[2].
     *
     * I extend my gratitude to Reddit user `/u/tenthmascot`[3] for the very extensive explanation. I would probably not
     * have been able to solve this without that explanation.
     *
     * I think there is improvement to be made to the code - better use the iterators of dotnet and maybe a different
     * representation of the buttons -, but it works. It works fast (~350ms) on my machine. And I think it remains
     * readable, which I value.
     *
     * [1]: https://www.reddit.com/r/adventofcode/comments/1pl8nsa/comment/ntqt12a
     * [2]: https://www.reddit.com/r/adventofcode/comments/1pk87hl/2025_day_10_part_2_bifurcate_your_way_to_victory/
     * [3]: https://www.reddit.com/user/tenthmascot/
     */

    /*
     * Step 1 is to calculate the patterns we can create, knowing that every button can be pressed once. We can calculate
     * this as we go, but the combinations will be the same for the entire row, and so we might as well calculate them
     * all and cache them.
     */
    var patterns = CalculateAllPatterns(i);
    
    /*
     * We are going to make use of memoization to speed up the entire process.
     */
    Dictionary<string, long> memoization = new ();

    /*
     * The main recursion; this function can be seen as f(joltage). If joltage is all zeroes then we can return 0, because
     * we require 0 button presses to reach the end. We will construct the pattern based on the odd values in the joltage,
     * and we match those against our pattern dictionary. For each combination of buttons we will perform f(joltage - buttons)
     * and we use the lowest value. Rinse and repeat, but be aware of the exit/cancel conditions.
     */
    long Solve(int[] joltage)
    {
        if (joltage.Sum() == 0)
        {
            // Console.WriteLine($"solve({string.Join(",", joltage)}) is done");
            return 0;
        }
        
        var key = string.Join(",", joltage);
        if (memoization.ContainsKey(key))
        {
            return memoization[key];
        }
        
        // Find odd joltages and convert to a pattern
        var pattern = JoltageToPattern(joltage);
        
        /*
         * If the pattern does not exist in our dictionary, then this is a dead end.
         * PSA! Do not try to be smart and use `long.MaxValue`, because this will cause an overflow, and therefore a
         * very, very low number...
         */
        if (!patterns.ContainsKey(pattern))
        {
            // Console.WriteLine($"Pattern {pattern} not found");
            return 10000000;
        }
        
        // Console.WriteLine($"Joltage: {{{string.Join(",", joltage)}}}, pattern: {pattern} ({patterns[pattern].Count} button combinations found)");
        
        long min = 10000000; // Again, don't use long.MaxValue!
        foreach (var buttons in patterns[pattern])
        {
            try
            {
                /*
                 * Press the buttons to come to an even joltage.
                 */
                var even = PressJoltage(joltage, buttons);
                // Console.WriteLine($"After pressing: {string.Join(", ", even)}");
                
                /*
                 * Now we can divide the joltage and multiply f(new_joltage) with 2 + the number of buttons pushed to
                 * come to an even joltage.
                 */
                var presses = (2 * Solve(even.Select(j => j/2).ToArray())) + buttons.Count;
                //Console.WriteLine($"solve({string.Join(",", joltage)}) with buttons: {string.Join(", ", buttons.Select(b => $"({string.Join(",", b)})"))}. After pressing: {buttons.Count} + 2 * solve({string.Join(", ", even.Select(j => j/2))}) = {presses}");
                
                if (presses < min)
                {
                    min = presses;
                }
            }
            catch (Exception)
            {
                /*
                 * We have dipped below 0, we can ignore this path.
                 * Maybe this should have been an explicit exit scenario in the recursion.
                 */
                //Console.WriteLine($"Below 0, skipping: {e.Message}");
            }
        }
        
        //Console.WriteLine($"Joltage: {{{string.Join(",", joltage)}}} is reachable in {min} presses");
        
        memoization[key] = min;
        
        return min;
    }
    
    return Solve(i.Joltage);
}

Dictionary<string, List<List<int[]>>> CalculateAllPatterns(Input i)
{
    var patterns = new Dictionary<string, List<List<int[]>>>();

    /*
     * A small recursive function to calculate all possible patterns based on the button inputs. As I found out the
     * hard way we also need "no buttons pressed" as a pattern.
     */
    void Calculate(string pattern, List<int[]> pressed, int[][] remaining)
    {
        if (remaining.Length == 0)
        {
            if (!patterns.ContainsKey(pattern)) patterns[pattern] = new();
            patterns[pattern].Add(pressed);

            return;
        }

        var current = remaining.First();
        Calculate(Press(pattern, current), With(pressed, current), remaining.Skip(1).ToArray());
        Calculate(pattern, pressed, remaining.Skip(1).ToArray());
    }
    Calculate(new string('.', i.Joltage.Length), new List<int[]>(), i.Buttons);
    
    return patterns;
}

List<T> With<T>(List<T> list, T item)
{
    return list.Concat(new[] { item }).ToList();
}

string JoltageToPattern(int[] joltage)
{
    var pattern = "";
    for (int i = 0; i < joltage.Length; i++)
    {
        pattern += joltage[i] % 2 == 0 ? '.' : '#';
    }
    return pattern;
}

int[] PressJoltage(int[] joltage, List<int[]> buttons)
{
    var j = (int[])joltage.Clone();

    foreach (var b in buttons)
    {
        foreach (var i in b)
        {
            j[i]--;
            if (j[i] < 0)
            {
                throw new Exception("We have reached new lows");
            }
        }
    }

    return j;
}