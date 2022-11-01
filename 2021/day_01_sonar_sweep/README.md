## --- Day 1: Sonar Sweep ---
You're minding your own business on a ship at sea when the overboard alarm goes off! You rush to see if you can help. Apparently, one of the Elves tripped and accidentally sent the sleigh keys flying into the ocean!

Before you know it, you're inside a submarine the Elves keep ready for situations like this. It's covered in Christmas lights (because of course it is), and it even has an experimental antenna that should be able to track the keys if you can boost its signal strength high enough; there's a little meter that indicates the antenna's signal strength by displaying 0-50 stars.

Your instincts tell you that in order to save Christmas, you'll need to get all fifty stars by December 25th.

Collect stars by solving puzzles.  Two puzzles will be made available on each day in the Advent calendar; the second puzzle is unlocked when you complete the first.  Each puzzle grants one star. Good luck!

As the submarine drops below the surface of the ocean, it automatically performs a sonar sweep of the nearby sea floor. On a small screen, the sonar sweep report (your puzzle input) appears: each line is a measurement of the sea floor depth as the sweep looks further and further away from the submarine.

For example, suppose you had the following report:

```
199
200
208
210
200
207
240
269
260
263
```

This report indicates that, scanning outward from the submarine, the sonar sweep found depths of <code>199</code>, <code>200</code>, <code>208</code>, <code>210</code>, and so on.

The first order of business is to figure out how quickly the depth increases, just so you know what you're dealing with - you never know if the keys will get carried into deeper water by an ocean current or a fish or something.

To do this, count **the number of times a depth measurement increases** from the previous measurement. (There is no measurement before the first measurement.) In the example above, the changes are as follows:

<pre><code>
199 (N/A - no previous measurement)
200 (<b>increased</b>)
208 (<b>increased</b>)
210 (<b>increased</b>)
200 (decreased)
207 (<b>increased</b>)
240 (<b>increased</b>)
269 (<b>increased</b>)
260 (decreased)
263 (<b>increased</b>)
</code></pre>

In this example, there are **<code>7</code>** measurements that are larger than the previous measurement.

**How many measurements are larger than the previous measurement?**


## --- Part Two ---
Considering every single measurement isn't as useful as you expected: there's just too much noise in the data.

Instead, consider sums of a **three-measurement sliding window**.  Again considering the above example:

```
199  A      
200  A B    
208  A B C  
210    B C D
200  E   C D
207  E F   D
240  E F G  
269    F G H
260      G H
263        H
```

Start by comparing the first and second three-measurement windows. The measurements in the first window are marked <code>A</code> (<code>199</code>, <code>200</code>, <code>208</code>); their sum is <code>199 + 200 + 208 = 607</code>. The second window is marked <code>B</code> (<code>200</code>, <code>208</code>, <code>210</code>); its sum is <code>618</code>. The sum of measurements in the second window is larger than the sum of the first, so this first comparison **increased**.

Your goal now is to count **the number of times the sum of measurements in this sliding window increases** from the previous sum. So, compare <code>A</code> with <code>B</code>, then compare <code>B</code> with <code>C</code>, then <code>C</code> with <code>D</code>, and so on. Stop when there aren't enough measurements left to create a new three-measurement sum.

In the above example, the sum of each three-measurement window is as follows:

<pre><code>
A: 607 (N/A - no previous sum)
B: 618 (<b>increased</b>)
C: 618 (no change)
D: 617 (decreased)
E: 647 (<b>increased</b>)
F: 716 (<b>increased</b>)
G: 769 (<b>increased</b>)
H: 792 (<b>increased</b>)
</code></pre>

In this example, there are **<code>5</code>** sums that are larger than the previous sum.

Consider sums of a three-measurement sliding window. **How many sums are larger than the previous sum?**
