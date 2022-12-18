using System.Reflection;

namespace Day17;

class Program
{
    public static void Main(string[] args)
    {
        bool DEBUG = args.Contains("debug");
        bool EXAMPLE = args.Contains("example");

        string[] input = EXAMPLE
            ? File.ReadAllLines($"{Path.GetDirectoryName(Assembly.GetExecutingAssembly().Location)}/example.txt")
            : File.ReadAllLines($"{Path.GetDirectoryName(Assembly.GetExecutingAssembly().Location)}/input.txt");

        // Part 1; Implement Tetris. That is basically it. If you have never implemented Tetris I recommend you watch
        // this great explanation from One Lone Coder: https://www.youtube.com/watch?v=8OK8_tHeCIA
        // Some remarks; No need to scan for line, which saves us a bunch. Another thing to consider is that we need
        // to count the height, so I have reverted the grid: upwards is higher y. I decided not to use a grid, but a
        // Dictionary. This should never really run out of limits. After that it is executing the instructions in the 
        // correct order. I have deliberately made the code extra verbose by using methods.
        
        int width = 7;

        Dictionary<(int x, long y), bool> field = new();
        int height = 0;

        string jets = input.First();
        int[][,] rocks = new int[5][,]
        {
            new int[4,4]
            {
                { 0, 0, 0, 0 }, 
                { 0, 0, 0, 0 }, 
                { 0, 0, 0, 0 }, 
                { 1, 1, 1, 1 }
            },
            new int[4,4]
            {
                { 0, 0, 0, 0 }, 
                { 0, 1, 0, 0 }, 
                { 1, 1, 1, 0 }, 
                { 0, 1, 0, 0 }
            },
            new int[4,4]
            {
                { 0, 0, 0, 0 }, 
                { 0, 0, 1, 0 }, 
                { 0, 0, 1, 0 }, 
                { 1, 1, 1, 0 }
            },
            new int[4,4] 
            {
                { 1, 0, 0, 0 }, 
                { 1, 0, 0, 0 }, 
                { 1, 0, 0, 0 }, 
                { 1, 0, 0, 0 }
            },
            new int[4,4] 
            {
                { 0, 0, 0, 0 }, 
                { 0, 0, 0, 0 }, 
                { 1, 1, 0, 0 }, 
                { 1, 1, 0, 0 }
            },
        };

        int jetIndex = 0;
        for (var rock = 0; rock < 2022; rock++)
        {
            //Console.WriteLine("A new rock begins falling:");
            
            // Drop rock
            int x = 2, // bottom left of block 
                y = height + 3;
            
            //Print(rock);

            var tetromino = rocks[rock % rocks.Length];
            
            ApplyJet();
            
            //Print(rock);
            
            while (CanMove(0, -1)) // moving down is decreasing y
            {
                //Console.WriteLine("Rock falls 1 unit:");
                // Drop
                Move(0, -1);
                //Console.WriteLine($"(x, y): ({x}, {y})");
                //Print(rock);
                ApplyJet();
                //Print(rock);
            }
            
            // We can no longer drop, lock in place
            //Console.WriteLine("Rock falls 1 unit, causing it to come to rest:");
            LockInPlace();
            Print(rock);

            height = Math.Max(height, y + HeightOfRock());

            bool CanMove(int dx, int dy)
            {
                if (dx == -1 && x == 0) return false; // we cannot move left
                if (dx == 1 && x == width - 1) return false; // we cannot move right
                if (dy == -1 && y == 0) return false; // we've hit bottom
                
                for (var row = 0; row < 4; row++)
                {
                    for (var col = 0; col < 4; col++)
                    {
                        if (tetromino[row, col] == 1 && field.ContainsKey((x: x + col + dx, y: y + dy + 3 - row)))
                        {
                            return false; // there is a block
                        }

                        if (tetromino[row, col] == 1 && x + col + dx >= width)
                        {
                            return false; // an edge of the tetromino is out of bounds on the right
                        }
                    }
                }
                
                return true;
            }

            void Move(int dx, int dy)
            {
                x += dx;
                y += dy;
            }

            void ApplyJet()
            {
                char jet = jets[jetIndex];

                if (jet == '>')
                {
                    if (CanMove(+1, 0))
                    {
                        // Move right
                        //Console.WriteLine("Jet of gas pushes rock right:");
                        Move(+1, 0);
                    }
                    else
                    {
                        //Console.WriteLine("Jet of gas pushes rock right, but nothing happens:");
                    }
                }

                if (jet == '<')
                {
                    if (CanMove(-1, 0))
                    {
                        // Move left
                        //Console.WriteLine("Jet of gas pushes rock left:");
                        Move(-1, 0);    
                    }
                    else
                    {
                        //Console.WriteLine("Jet of gas pushes rock left, but nothing happens:");
                    }
                }
                
                jetIndex = (jetIndex + 1) % jets.Length; // loop around
            }

            void LockInPlace()
            {
                //Console.WriteLine($"LockInPlace: ({x}, {y})");
                // Copy all values to the field
                for (var row = 3; row >= 0; row--)
                {
                    for (var col = 0; col < 4; col++)
                    {
                        if (tetromino[row, col] == 1)
                        {
                            field[(x: x + col, y: y + 3 - row)] = true;
                        }
                    }
                }
            }

            int HeightOfRock()
            {
                switch (rock % rocks.Length)
                {
                    case 0:
                        return 1;
                    case 1:
                    case 2:
                        return 3;
                    case 3:
                        return 4;
                    case 4:
                        return 2;
                    default:
                        throw new Exception("Unknown tetromino");
                }
            }

            void Print(int? rIndex = null)
            {
                if (!DEBUG) return;

                for (var row = height + 3; row >= 0; row--)
                {
                    Console.Write('|');
                    for (var col = 0; col < width; col++)
                    {
                        if (rIndex != null)
                        {
                            
                        }
                        Console.Write(field.ContainsKey((x: col, y: row)) ? '#' : '.');
                    }
                    Console.Write('|');
                    Console.WriteLine("");
                }
                Console.WriteLine("+-------+");
                Console.WriteLine("");
            }
        }
        
        Console.WriteLine($"How many units tall will the tower of rocks be after 2022 rocks have stopped falling? {height}");
    }
}