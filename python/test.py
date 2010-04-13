#!/usr/bin/env python

def dec2bin(num):
    bin = ""
    i = 0
    while (num > 0):
        if (num % 2 == 1):
            bin = "1" + bin
        else:
            bin = "0" + bin
        num = num / 2
        i = i + 1
    for j in range(i, 32):
        bin = "0" + bin
    return bin

import sys

i = 0
print("Frame Value:")
sys.stdout.flush()
i = input()

print "     ", " 3         2         1"
print "     ", "10987654321098765432109876543210"
print "bin: ", dec2bin(i)
