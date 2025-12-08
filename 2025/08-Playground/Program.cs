using _08_Playground;
using Shared;

string[] input = File.ReadAllLines(Environment.GetEnvironmentVariable("INPUT") != null ? $"./{Environment.GetEnvironmentVariable("INPUT")}" : "./input.txt");
int iterations = Environment.GetEnvironmentVariable("ITERATIONS") != null ? int.Parse(Environment.GetEnvironmentVariable("ITERATIONS")) : 1000;

// Parse input
var boxes = input.Select(line => JunctionBox.FromInput(line)).ToArray();

// Part 01
var timer = new AocTimer();
// perform calculation
var one = PartOne((JunctionBox[])boxes.Clone());
timer.Duration($"Wat do you get if you multiply together the sizes of the three largest circuits? {one}");

// Part 02
timer = new AocTimer();
// perform calculation
var two = PartTwo((JunctionBox[])boxes.Clone());
timer.Duration($"What do you get if you multiply together the X coordinates of the last two junction boxes you need to connect? {two}");

int PartOne(JunctionBox[] boxes)
{
    Dictionary<(int, int), double> distances = CalculateDistances(boxes);

    var sorted = distances.OrderBy(x => x.Value).Take(iterations);
    
    Dictionary<int, List<int>> circuits = new Dictionary<int, List<int>>();

    foreach (var closest in sorted)
    {
        int left = closest.Key.Item1;
        int right = closest.Key.Item2;

        // Check in what circuit the connections need to go
        if (boxes[left].Circuit == null && boxes[right].Circuit == null)
        {
            // neither is part of an existing circuit
            circuits[circuits.Count + 1] = new List<int>{ left, right };
            boxes[left].Circuit = boxes[right].Circuit = circuits.Count;
        } else if (boxes[left].Circuit == boxes[right].Circuit)
        {
            // they are already part of the same circuit
            continue;
        } else if (boxes[left].Circuit == null)
        {
            boxes[left].Circuit = boxes[right].Circuit;
            circuits[(int)boxes[right].Circuit].Add(left);
        } else if (boxes[right].Circuit == null)
        {
            boxes[right].Circuit = boxes[left].Circuit;
            circuits[(int)boxes[left].Circuit].Add(right);
        } else if (boxes[left].Circuit != boxes[right].Circuit)
        {
            int to = (int)boxes[left].Circuit;
            int from = (int)boxes[right].Circuit;

            foreach (var moving in circuits[from])
            {
                boxes[moving].Circuit = to;
                circuits[to].Add(moving);
            }
            circuits[from].Clear();
        } 
        else
        {
            throw new Exception("This should not happen");
        }
    }
    
    var ordered = circuits.OrderByDescending(x => x.Value.Count).Take(3).Select(x => x.Value.Count).ToArray();
    return ordered[0] * ordered[1] * ordered[2];
}

long PartTwo(JunctionBox[] boxes)
{
    Dictionary<(int, int), double> distances = CalculateDistances(boxes);

    // I am not familiar enough with C# to make a deep clone, but because we keep state in our input array we need to
    // reset all the circuits.
    foreach (var box in boxes) box.Circuit = null;
    
    var sorted = distances.OrderBy(x => x.Value);
    
    Dictionary<int, List<int>> circuits = new Dictionary<int, List<int>>();

    int circuitId = 0;
    int boxCount = 0;
    foreach (var closest in sorted)
    {
        int left = closest.Key.Item1;
        int right = closest.Key.Item2;

        // Check in what circuit the connections need to go
        if (boxes[left].Circuit == null && boxes[right].Circuit == null)
        {
            // neither is part of an existing circuit
            circuitId++;
            boxCount += 2;
            circuits[circuitId] = new List<int>{ left, right };
            boxes[left].Circuit = boxes[right].Circuit = circuitId;
        } else if (boxes[left].Circuit == boxes[right].Circuit)
        {
            // they are already part of the same circuit
            continue;
        } else if (boxes[left].Circuit == null)
        {
            boxCount++;
            boxes[left].Circuit = boxes[right].Circuit;
            circuits[(int)boxes[right].Circuit].Add(left);
        } else if (boxes[right].Circuit == null)
        {
            boxCount++;
            boxes[right].Circuit = boxes[left].Circuit;
            circuits[(int)boxes[left].Circuit].Add(right);
        } else if (boxes[left].Circuit != boxes[right].Circuit)
        {
            int to = (int)boxes[left].Circuit;
            int from = (int)boxes[right].Circuit;

            foreach (var moving in circuits[from])
            {
                boxes[moving].Circuit = to;
                circuits[to].Add(moving);
            }

            circuits.Remove(from);
        } 
        else
        {
            throw new Exception("This should not happen");
        }

        if (boxCount == boxes.Length && circuits.Count == 1)
        {
            // I need to cast to long, because .NET will use 32-bit space when multiplying integers
            return (long)boxes[left].X * (long)boxes[right].X;
        }
    }

    return 0;
}

Dictionary<(int, int), double> CalculateDistances(JunctionBox[] boxes)
{
    var distances = new Dictionary<(int, int), double>();
    for (int i = 0; i < boxes.Length - 1; i++)
    {
        for (int j = i + 1; j < boxes.Length; j++)
        {
            distances.Add((i, j), Distance(boxes[i], boxes[j]));
        }
    }

    return distances;
}

double Distance(JunctionBox box1, JunctionBox box2)
{
    // see https://en.wikipedia.org/wiki/Euclidean_distance
    return Math.Sqrt(Math.Pow(box1.X - box2.X, 2) + Math.Pow(box1.Y - box2.Y, 2) + Math.Pow(box1.Z - box2.Z, 2));
}