#!/usr/bin/env python
import MySQLdb

import MySQLdb

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

class node:
    """Simple Solar System Node Class"""
    
    def __init__(self, name):
        self.name = name
        cursor.execute("SELECT solarSystemID, x, y, z "+ \
                       "FROM mapsolarsystems " + \
                       "WHERE solarSystemName = '%s'" % (self.name))
        
        (self.ID, self.x, self.y, self.z) = cursor.fetchone()
        self.predecessor = None
        
    def distance(node):
        return ((this.x - node.x)**2 + (this.y - node.y)**2 + (this.z - node.z)**2)

class list:
    """Exploration list for A*"""
    
    def __init__(self):
        this.entries = []
    
    def add(self, node):
        this.entries.add(node)
        
    def remove(self, node):
        this.entries.remove(node)
        
    def removeMin(self):
        min = 0
        for nodes in this.entries:
            if (nodes.fromstart < min):
                min = nodes
        this.entries.remove(min)
        return min

class astar:
    
    def __init__(self, startName, destName):
        self.startNode = node(startName)
        self.destNode = node(destName)
        
    def getRoute(self):
        pass
