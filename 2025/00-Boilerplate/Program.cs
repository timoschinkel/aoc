using System.Reflection;
using Shared;

string path = Path.GetDirectoryName(Assembly.GetExecutingAssembly().Location) ?? ".";
string[] input = File.ReadAllLines(Environment.GetEnvironmentVariable("INPUT") != null ? $"{path}/{Environment.GetEnvironmentVariable("INPUT")}" : $"{path}/input.txt");

// Parse input

// Part 01
var timer = new AocTimer();
// perform calculation
timer.Duration($"The answer is: {0}");

// Part 02
timer = new AocTimer();
// perform calculation
timer.Duration($"The answer is: {0}");
