#!/usr/bin/env python

import MySQLdb
import sys
from time import clock
from copy import deepcopy

mysql_opts = { 
    'host': "localhost", 
    'user': "root", 
    'pass': "", 
    'db':   "eve_online" 
    } 
mysql = MySQLdb.connect(mysql_opts['host'], mysql_opts['user'], mysql_opts['pass'], mysql_opts['db'])
mysql.apilevel = "2.0" 
mysql.threadsafety = 2 
mysql.paramstyle = "format"
cursor = mysql.cursor()


systems = []
jumps = []
idtoint = {}
inttoid = {}
i = 0

#generate systemlist with distance
cursor.execute("""SELECT solarSystemID
                  FROM mapsolarsystems
                  WHERE solarSystemID < 31000000
                  ORDER BY solarSystemID""")
result = cursor.fetchall()

for system in result:
    systems.append([int(system[0]), sys.maxint])
    inttoid[i] = int(system[0])
    idtoint[int(system[0])] = i
    i = i + 1
size = len(systems)

#generate jumplist
cursor.execute("""SELECT fromSolarSystemID, toSolarSystemID
                  FROM mapsolarsystemjumps""")
result = cursor.fetchall()
for (fromID, toID) in result:
    jumps.append((int(fromID), int(toID)))
    
#calc starting point, and remove the last entry for safety
cursor.execute("""SELECT max(fromSolarSystemID)
                  FROM mapdistance""")
result = cursor.fetchall()

if (result[0][0] != None):
    calcStart = idtoint[result[0][0]]
    print("Last System: %i. Deleting and re-calculating." % inttoid[calcStart])
else:
    print("No distance values found. Calculating from the beginning.")
    calcStart = 0

cursor.execute("""DELETE FROM mapdistance
                  WHERE fromSolarSystemID = %i""" % (inttoid[calcStart]))

cursor.execute("""SELECT max(SolarSystemID)
                  FROM mapsolarsystems
                  WHERE solarSystemID < 31000000""")
result = cursor.fetchall()
calcEnd = idtoint[result[0][0]]

print ("Calculating from %i to %i" % (inttoid[calcStart], inttoid[calcEnd]))

#start bellman ford
for startSystem in range(calcStart, calcEnd + 1):
    startTime = clock()
    calcSystems = deepcopy(systems)
    calcSystems[startSystem][1] = 0
    
    for i in xrange(0, size - 1):
        for (fromID, toID) in jumps:
            if ((calcSystems[idtoint[fromID]][1] + 1) < calcSystems[idtoint[toID]][1]):
                calcSystems[idtoint[toID]][1] = calcSystems[idtoint[fromID]][1] + 1
    
    for (system, distance) in calcSystems:
        #print ("%i -> %i: %i Jumps" % (startSystem, system, distance))
        cursor.execute("""INSERT INTO mapdistance
                          VALUES(%i, %i, %i)""" % (inttoid[startSystem], system, distance))

    del calcSystems
    endTime = clock()
    print("%i done (%.2f)" % (inttoid[startSystem], endTime - startTime))
    sys.stdout.flush()