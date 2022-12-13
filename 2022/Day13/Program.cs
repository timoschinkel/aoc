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
        
        // Part 2; I had more trouble with adding the two additional codes as some functions are pure and others aren't.
        // Take all the lines, remove all the empty lines, add the two additional codes. Now we have a list of all codes
        // and by using the built-in sorting algorithm we can use the outcome of IsGood() to tell if one is smaller than
        // the other. Search the list for the two added codes - I opted to convert back to a JSON string, as I did not
        // feel like writing compare/equals functions - and adjust from zero-based lists to one-based puzzles.
        
        // Post mortem; A puzzle that was more tricky that I had anticipated. The big epiphany was that the input is
        // valid JSON. But that was replaced with worries about how to handle untyped JSON parsing in C#. The key proved
        // to be reading the examples real good.
        
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

        input = input.Append("[[2]]").Append("[[6]]").ToArray();
        var list = input.ToList()
            .FindAll(line => !line.Equals(""))
            .Select(line => JsonNode.Parse(line).AsArray())
            .ToList();
        list.Sort(((one, another) => IsGood(one, another) == false ? 1 : -1));
        
        var two = list.Select(json => json.ToJsonString()).ToList().FindIndex((s => s.Equals("[[2]]"))) + 1;
        var six = list.Select(json => json.ToJsonString()).ToList().FindIndex((s => s.Equals("[[6]]"))) + 1;

        Console.WriteLine($"What is the decoder key for the distress signal? {two*six}");

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