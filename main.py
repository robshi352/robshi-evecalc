#!/usr/bin/env python

import MySQLdb
from distance_calc import *

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

#newNode = node("Jita")
#print(newNode.ID)

cursor.close()