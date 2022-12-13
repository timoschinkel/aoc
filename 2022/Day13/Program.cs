using System.Reflection;
using System.Text.Json.Nodes;
using System.Text.Json.Serialization;

namespace Day13;

class Program
{
    public static void Main(string[] args)
    {
        bool DEBUG = args.Contains("debug");

        string[] input = File.ReadAllLines($"{Path.GetDirectoryName(Assembly.GetExecutingAssembly().Location)}/input.txt");

        // Part 1; Again it is all about reading and interpreting the examples - I cannot emphasize enough how good the
        // examples appear to be this year! But also reading the input. In PHP I would have opted to use `eval()`, but
        // I realised that the input is valid JSON. Using JsonNode.Parse() the input strings can be converted to objects
        // of type JsonNode. Those can be split out into objects of type JsonArray and JsonValue. I perform some dirty
        // tricks to convert values to arrays if needed: JsonNode.Parse($"[{l.ToJsonString()}]").AsArray()
        // For the comparison I eventually resorted to implementing the rules as they are explained in the examples 
        // almost verbatim.
        
        var pair = 0;
        var score = 0;
        for (var i = 0; i < input.Length; i += 3)
        {
            pair++;

            var left = JsonNode.Parse(input[i]).AsArray();
            var right = JsonNode.Parse(input[i + 1]).AsArray();

            var good = IsGood(left, right);
            if (good == true)
            {
                score += pair;
            }
            
            if (DEBUG) Console.WriteLine($"Pair {pair} ({input[i]}, {input[i + 1]}) => {good.ToString()}");
        }
        
        Console.WriteLine($"What is the sum of the indices of those pairs? {score}");

        bool? IsGood(JsonArray left, JsonArray right)
        {
            // iterate over values
            for (int i = 0; i < Math.Min(left.AsArray().Count, right.AsArray().Count); i++)
            {
                var l = left[i];
                var r = right[i];

                if (r.GetType() == typeof(JsonArray) || l.GetType() == typeof(JsonArray))
                {
                    // delegate
                    var isGood = IsGood(
                        l.GetType() == typeof(JsonArray) ? l.AsArray() : JsonNode.Parse($"[{l.ToJsonString()}]").AsArray(),
                        r.GetType() == typeof(JsonArray) ? r.AsArray() : JsonNode.Parse($"[{r.ToJsonString()}]").AsArray()
                    );

                    if (isGood != null)
                    {
                        return isGood;
                    }

                    continue;
                }

                if ((int)l < (int)r)
                {
                    return true;
                }

                if ((int)l > (int)r)
                {
                    return false;
                }
            }
            
            // We made it so far, and if we made it to here the lists are the same until now.
            if (left.Count > right.Count)
            {
                // Right side ran out of items, so inputs are not in the right order
                return false;
            }

            if (left.Count < right.Count)
            {
                // Left side ran out of items, so inputs are in the right order
                return true;
            }

            return null;
        }
    }
}