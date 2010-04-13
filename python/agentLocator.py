#!/usr/bin/env python

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

class agentLocator:
    
    def __init__(self):
        pass
    
    def set(self, corp, base):
        self.corp = corp
        self.base = base
    
    def locate(self):
        
        cursor.execute("SELECT level, quality, solarSystemName, t4.security " +\
                       "FROM agtagents NATURAL JOIN crpnpccorporations as t1, evenames as t2, stastations as t3, mapsolarsystems as t4 " +\
                       "WHERE t1.corporationID = t2.itemID " +\
                       "AND t4.solarSystemID = t3.solarSystemID " +\
                       "AND locationID = t3.stationID " +\
                       "AND t2.itemName = '%s'"  % (self.corp) +\
                       "Order BY t4.solarSystemName, level, quality")
        agentList = cursor.fetchall()
        return agentList