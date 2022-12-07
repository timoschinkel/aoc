string[] input = File.ReadAllLines(@"./input.txt");

// Read input

string folder = "";
Dictionary<string, long> filesystem = new Dictionary<string, long>();
Dictionary<string, long> recursive = new Dictionary<string, long>();

foreach (var line in input)
{
    if (line.StartsWith("$ cd"))
    {   // change directory
        if (line.Equals("$ cd /")) folder = "";
        else if (line.Equals("$ cd ..")) folder = String.Join('/', folder.Split("/").SkipLast(1));
        else folder += "/" + line.Replace("$ cd ", "");
        continue;
    }

    if (line.StartsWith("$ ls") || line.StartsWith("dir"))
    {   // list, noop
        continue;
    }

    // Read file including size
    String[] parts = line.Split(" ");
    long size = Int64.Parse(parts[0]);
    
    filesystem[folder] = filesystem.GetValueOrDefault(folder, 0) + size;

    string[] folderParts = folder.Split("/");
    for (var i = 0; i < folderParts.Length; i++)
    {
        string f = String.Join('/', folderParts.SkipLast(i));
        recursive[f] = recursive.GetValueOrDefault(f, 0) + size;
    }
}

long score = 0;
foreach (var f in recursive)
{
    if (f.Key != "" && f.Value <= 100000)
    {
        score += f.Value;
    }
}

Console.WriteLine($"What is the sum of the total sizes of those directories? {score}");

var total = recursive.GetValueOrDefault("", 0);
var toFreeUp = 30000000 - (70000000 - total);
// Console.WriteLine($"Total: {total}, to free up: {toFreeUp}");

var toDelete = recursive.Where(f => f.Value >= toFreeUp).OrderBy(f => f.Value).First();
Console.WriteLine($"What is the total size of that directory? {toDelete.Value}");