#!/usr/bin/env python

import MySQLdb
import distance_calc
from distance_calc import *

distance_calc.debugLevel = 1

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

myAStar = astar("Jita", "Halaima")
route = myAStar.getRoute()
for names in route:
    print names
cursor.close()