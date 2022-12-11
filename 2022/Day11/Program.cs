namespace Day11;

class Program
{
    public static void Main(string[] args)
    {
        bool DEBUG = args.Contains("debug");

        // Part 1; Iterate every monkey and inspect every item's worry level. This puzzle is mostly about keeping a 
        // proper administration of worry levels and updating monkeys where needed. For the first time this year I have
        // introduced an object. Reason was for better scoping. I also opted to hardcode my puzzle input. Most important
        // reason for that is the calculation that converts a new value from an old value. I don't want to spend too 
        // much time on that.
        //
        // Part 2 is about number theory/algebra, and a little bit about integer space. I had to change a number of 
        // properties from integer (signed 32-bit in C#) to long (signed 64-bit in C#). But I still ran out of the
        // limits about worry levels. The worry levels need to be kept within the (now) signed 64-bit space. We need 
        // to find out how to reduce the worry levels. The divider for every monkey is not enough, as we don't know 
        // exactly where the item will go next. A generic solution is to divide by the common divider of all monkeys 
        // with every inspection. A quick glance at the example - and input - showed that all dividers are prime. So
        // the common divider between these dividers is the product of all dividers. Glad I opted for the long numeric
        // type...
        // PS I did try to use C#'s BigInteger type, but that barely made it past level 20 within an acceptable time.
        //
        // Post mortem; I had the idea for the common divider, but initially discarded it as the dividers were all
        // prime numbers. I had to look up some hints on Reddit to actually circle back to that approach. I was also
        // very happy with the extensive example and my debug outputs - just add `debug` to the execution command - as
        // it showed me that the number of inspections were correct, but that the eventual score was off. This also was
        // due to the available space in the int numeric type. Switching to long fixed that as well.

        // Part 1
        
        var monkeys = FromInput(args.Contains("example"));

        for (var round = 1; round <= 20; round++)
        {
            foreach (var monkey in monkeys)
            {
                // Let the Monkey take care of inspection. If Monkey.Inspect() returns an integer, then that is the 
                // identifier of the monkey where the item is tossed to. When Monkey.Inspect() returns null, then the
                // monkey has no more items.
                var target = monkey.Inspect();
                while (target != null)
                {
                    var newWorryLevel = target.Value.Item1;
                    var identifier = target.Value.Item2;
                    
                    monkeys[identifier].Items.Add(newWorryLevel);
                    
                    target = monkey.Inspect();
                }
            }
            
            if (DEBUG)
            {
                Console.WriteLine($"After round {round} the monkeys are holding items with these worry levels:");
                foreach (var monkey in monkeys)
                {
                    Console.WriteLine($"Monkey {monkey.Identifier}: {String.Join(", ", monkey.Items)}");
                }
                Console.WriteLine("");
            }
        }

        monkeys.Sort(((one, another) => another.Inspections - one.Inspections));
        
        if (DEBUG)
        {
            foreach (var monkey in monkeys)
            {
                Console.WriteLine($"Monkey {monkey.Identifier} inspected items {monkey.Inspections} times");
            }
            Console.WriteLine("");
        }

        var score = monkeys[0].Inspections * monkeys[1].Inspections;
        Console.WriteLine($"What is the level of monkey business after 20 rounds of stuff-slinging simian shenanigans? {score}");
        
         // Part 2
         long commonDivider = monkeys.Select(monkey => (long)monkey.Divisible).Aggregate((long)1, (l, l1) => l * l1);
         monkeys = FromInput(args.Contains("example"));

         for (var round = 1; round <= 10000; round++)
         {
             foreach (var monkey in monkeys)
             {
                 // Let the Monkey take care of inspection. If Monkey.Inspect() returns an integer, then that is the 
                 // identifier of the monkey where the item is tossed to. When Monkey.Inspect() returns null, then the
                 // monkey has no more items.
                 var target = monkey.Inspect2(commonDivider);
                 while (target != null)
                 {
                     var newWorryLevel = target.Value.Item1;
                     var identifier = target.Value.Item2;
                    
                     monkeys[identifier].Items.Add(newWorryLevel);
                    
                     target = monkey.Inspect2(commonDivider);
                 }
             }
            
             if (DEBUG && (round == 1 || round == 20 || round % 1000 == 0))
             {
                 Console.WriteLine($"== After round {round} ==");
                 foreach (var monkey in monkeys)
                 {
                     Console.WriteLine($"Monkey {monkey.Identifier} inspected items {monkey.Inspections} times");
                 }
                 Console.WriteLine("");
             }
         }

         monkeys.Sort(((one, another) => another.Inspections - one.Inspections));
        
         if (DEBUG)
         {
             foreach (var monkey in monkeys)
             {
                 Console.WriteLine($"Monkey {monkey.Identifier} inspected items {monkey.Inspections} times");
             }
             Console.WriteLine("");
         }

         var score2 = (long)monkeys[0].Inspections * (long)monkeys[1].Inspections;
         Console.WriteLine($"What is the level of monkey business after 20 rounds of stuff-slinging simian shenanigans? {score2}");
    }

    private static List<Monkey> FromInput(bool example)
    {
        // Example
        if (example)
        {
            return new List<Monkey>
            {
                new Monkey(
                    0,
                    new List<long> { 79, 98 },
                    old => old * 19,
                    23, 2, 3),
                new Monkey(
                    1,
                    new List<long> { 54, 65, 75, 74 },
                    old => old + 6,
                    19, 2, 0),
                new Monkey(
                    2,
                    new List<long> { 79, 60, 97 },
                    old => old * old,
                    13, 1, 3),
                new Monkey(
                    3,
                    new List<long> { 74 },
                    old => old + 3,
                    17, 0, 1),
            };
        }

        // Puzzle input
        return new List<Monkey>
        {
            new Monkey(
                0, 
                new List<long> { 83, 62, 93 }, 
                old => old * 17,
                2, 1, 6),
            new Monkey(
                1, 
                new List<long> { 90, 55 }, 
                old => old + 1,
                17, 6, 3),
            new Monkey(
                2, 
                new List<long> { 91, 78, 80, 97, 79, 88 }, 
                old => old + 3,
                19, 7, 5),
            new Monkey(
                3, 
                new List<long> { 64, 80, 83, 89, 59 }, 
                old => old + 5,
                3, 7, 2),
            new Monkey(
                4, 
                new List<long> { 98, 92, 99, 51 }, 
                old => old * old,
                5, 0, 1),
            new Monkey(
                5, 
                new List<long> { 68, 57, 95, 85, 98, 75, 98, 75 }, 
                old => old + 2,
                13, 4, 0),
            new Monkey(
                6, 
                new List<long> { 74 }, 
                old => old + 4,
                7, 3, 2),
            new Monkey(
                7, 
                new List<long> { 68, 64, 60, 68, 87, 80, 82 }, 
                old => old * 19,
                11, 4, 5),
        };
    }

    class Monkey
    {
        private readonly Operation _operation;
        public int Identifier { get; }
        public List<long> Items { get; }
        public int Divisible { get; }
        public int TargetTrue { get; }
        public int TargetFalse { get; }
        
        public int Inspections { get; set; }

        public delegate long Operation(long old);

        public Monkey(
            int identifier,
            List<long> items,
            Operation operation,
            int divisible,
            int targetTrue,
            int targetFalse
        )
        {
            _operation = operation;
            Identifier = identifier;
            Items = items;
            Divisible = divisible;
            TargetTrue = targetTrue;
            TargetFalse = targetFalse;
        }
        
        public (long, int)? Inspect()
        {
            if (Items.Count == 0)
            {
                return null;
            }

            Inspections++;

            var item = Items.First();
            Items.RemoveAt(0);
            
            long newWorryLevel = _operation(item) / 3;
            
            return (
                newWorryLevel,
                newWorryLevel % Divisible == 0 ? TargetTrue : TargetFalse
            );
        }
        
        public (long, int)? Inspect2(long commonDivider)
        {
            if (Items.Count == 0)
            {
                return null;
            }

            Inspections++;

            var item = Items.First();
            Items.RemoveAt(0);

            var newWorryLevel = _operation(item) % commonDivider;
            
            return (
                newWorryLevel,
                newWorryLevel % Divisible == 0 ? TargetTrue : TargetFalse
            );
        }
    }
}