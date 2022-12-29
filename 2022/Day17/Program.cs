using System.Diagnostics;
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

        var timer = new Stopwatch();
        
        // Part 1; Implement Tetris. That is basically it. If you have never implemented Tetris I recommend you watch
        // this great explanation from One Lone Coder: https://www.youtube.com/watch?v=8OK8_tHeCIA
        // Some remarks; No need to scan for line, which saves us a bunch. Another thing to consider is that we need
        // to count the height, so I have reverted the grid: upwards is higher y. I decided not to use a grid, but a
        // Dictionary. This should never really run out of limits. After that it is executing the instructions in the 
        // correct order. I have deliberately made the code extra verbose by using methods.
        //
        // Part 2; Pattern finding. We start with a flat surface, and I don't know what the consequences of this are. I
        // opted to create a pattern for 50 rows starting at rock 2022 - which we need to calculate anyway. I keep
        // dropping rocks until I match the pattern. At this point we can calculate the number of repetitions of the 
        // pattern, and the height gain per repetition. We only need to drop the remaining rocks to meet the target
        // number of rocks.
        
        int width = 7;

        Dictionary<(int x, long y), bool> field = new();
        long height = 0;

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

        timer.Start();
        
        int jetIndex = 0;
        string patternAt2022 = "";
        long heightAt2022 = 0;

        long repetitions = 0, heightGain = 0;
        
        long target = 1000000000000;
        for (long rock = 0; rock < target; rock++)
        {
            // Drop rock
            int x = 2; // bottom left of block 
            long y = height + 3;
            
            var tetromino = rocks[rock % rocks.Length];
            ApplyJet();
            
            while (CanMove(0, -1)) // moving down is decreasing y
            {
                // Drop
                Move(0, -1);
                //Print(rock);
                ApplyJet();
            }
            
            // We can no longer drop, lock in place
            //Console.WriteLine("Rock falls 1 unit, causing it to come to rest:");
            LockInPlace();

            height = Math.Max(height, y + HeightOfRock());

            if (rock == 2021)
            {
                // Once we reach the 2022th rock, we can finish part one.
                FinishPartOne();
                
                // We can now create a pattern of the last 50 rows. 50 is an arbitrary number, but it worked, so why
                // change it. Maybe using the LCM of the length of the jets and the number of blocks works as well.
                // I create a string pattern and store it. After this we continue dropping rocks until we find a match.
                patternAt2022 = GetPatternFromHeight(height - 1, 50);
                heightAt2022 = height;
                continue;
            }
            else if (rock > 2021 && repetitions == 0)
            {
                // Check if the pattern matches that of that at rock 2022
                string pattern = GetPatternFromHeight(height - 1, 50);

                if (pattern == patternAt2022)
                {
                    // We have found a pattern of size 50! Now we can calculate the amount of repetitions we can see
                    // until we reach the target. We do this by a integer division. The modulo is the number of rocks
                    // we need to drop to get towards the target. I store the number of repetitions, together with the
                    // height gain per repetition. We update `rock` so we only perform the last required number of rock
                    // drops.
                    
                    long rockGain = rock - 2021;
                    heightGain = height - heightAt2022;
                    if (DEBUG) Console.WriteLine($"Match found at rock {rock + 1}! Every {rockGain} rocks we gain {heightGain} height.");
                    
                    repetitions = (target - rock) / rockGain;
                    long rocksLeft = (target - rock) % rockGain;
                    if (DEBUG) Console.WriteLine($"We will still have {target} - {rock} / {rockGain} = {repetitions} left, and then we still need to drop {rocksLeft} rocks");
                    
                    rock = target - rocksLeft;
                }
            }

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
                        Move(+1, 0);
                    }
                }

                if (jet == '<')
                {
                    if (CanMove(-1, 0))
                    {
                        // Move left
                        Move(-1, 0);    
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

        long finalHeight = height + (repetitions * heightGain);
        
        timer.Stop();
        Console.WriteLine($"How many units tall will the tower of rocks be after {target} rocks have stopped falling? {finalHeight} ({timer.ElapsedMilliseconds}ms)");

        string GetPatternFromHeight(long h, int length)
        {
            string pattern = "";
            for (var y = h; y > h - length; y--)
            {
                for (var x = 0; x < width; x++)
                {
                    pattern += field.ContainsKey((x: x, y: y)) ? '1': '0';
                }
            }
            return pattern;
        }

        void FinishPartOne()
        {
            Console.WriteLine($"How many units tall will the tower of rocks be after 2022 rocks have stopped falling? {height} ({timer.ElapsedMilliseconds}ms)");       
        }
    }
}