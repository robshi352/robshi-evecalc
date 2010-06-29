#!/usr/bin/env python

import MySQLdb
import sys
import time
import numpy

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

cursor.execute("""SELECT solarSystemID
                  FROM mapsolarsystems
                  WHERE solarSystemID < 31000000
                  ORDER BY solarSystemID""")
result = cursor.fetchall()

systems = []
idtoint = {}
inttoid = {}
i = 0

for system in result:
    systems.append(int(system[0]))
    inttoid[i] = int(system[0])
    idtoint[int(system[0])] = i
    i = i + 1
size = len(systems)
del result

dist=numpy.ones((size, size), dtype=int)

#init with maxint value
for i in xrange(0, size):
    for j in xrange(0, size):
        dist[i][j] = sys.maxint

for i in xrange(0, size):
    dist[i][i] = 0

cursor.execute("""SELECT fromSolarSystemID, toSolarSystemID
                  FROM mapsolarsystemjumps
                  ORDER BY fromSolarSystemID, toSolarSystemID""")
#WHERE fromSolarSystemID < 31000000
result = cursor.fetchall()

#init with the direct jumps
for (fromID, toID) in result:
    #if ((fromID < 31000000) and (toID < 31000000)):
        dist[idtoint[fromID]][idtoint[toID]] = 1
        dist[idtoint[toID]][idtoint[fromID]] = 1
print("systemcount: %i" % (size))
print("setup done")
sys.stdout.flush()
end = time.clock()

for k in range(0, size):
    #if (k % 10 == 0):
    start = end
    end = time.clock()
    print("k: %i (%i%%) - %.2fs" % (k, k * 100 / size, end - start))
    sys.stdout.flush()
    for i in range(0, size):
        #if (i % 100 == 0):
        #    print("i: %i (%i%%)" % (i, i * 100 / size))
        #sys.stdout.flush()
        for j in range(0, size):
            dist[i][j] = min(dist[i][j], dist[i][k] + dist[k][j])

for i in xrange(0, size):
    for j in xrange(0, size):
        cursor.execute("""INSERT INTO mapdistance
                          VALUES(%i, %i, %i)""" % (inttoid[i], inttoid[j], dist[i][j]))
        result = cursor.fetchall()