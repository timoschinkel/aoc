namespace _10_Factory;

public class Input (string target, int[][] buttons, int[] joltage)
{
    public string Target { get; } = target;
    public int[][] Buttons { get; } = buttons;
    public int[] Joltage { get; } = joltage;

    public static Input FromString(string input)
    {
        // [.##.] (3) (1,3) (2) (2,3) (0,2) (0,1) {3,5,4,7}
        var parts = input.Split(" ");
        
        // [.##.]
        var target = parts[0].Substring(1, parts[0].Length - 2);
        
        var buttons = parts.Skip(1).Take(parts.Length - 2).Select(seq => seq.Substring(1, seq.Length - 2).Split(",").Select(int.Parse).ToArray()).ToArray();
        
        // {3,5,4,7}
        var joltage = parts[^1].Substring(1, parts[^1].Length - 2).Split(",").Select(int.Parse).ToArray();
        
        return new Input(target, buttons, joltage);
    }
}