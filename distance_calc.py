#!/usr/bin/env python
import MySQLdb

import MySQLdb
import sys

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
        
    def distance(self, node):
        """calculates the coordinate-based distance to the node"""
        return ((this.x - node.x)**2 + (this.y - node.y)**2 + (this.z - node.z)**2)
    
    def expand(self):
        """selects all adjancents nodes"""
        cursor.execute("SELECT solarSystemID " +\
                       "FROM mapsolarSystems AS t1, mapsolarsystemjumps AS t2 " +\
                       "WHERE t1.solarSystemID = t2.toSolarSystemID " +\
                       "AND t2.fromSolarSystemID = %i" % (self.ID))
        expandList = cursor.fetchall()
        returnList = []
        for toSolarSystemID in expandList:
            returnlist.append(toSolarSystemID[0])
        return returnList
    
class myList:
    """Exploration list for A*"""
    
    def __init__(self):
        self.entries = []
    
    def add(self, node, dist):
        """adds an element to the list"""
        self.entries.append((node, dist))
        
    def removeMin(self):
        """will return and remove the minimal node"""
        min = sys.maxint
        mindist = 0
        for (nodes, dist) in self.entries:
            if (dist < min):
                min = nodes
                mindist = dist
        self.entries.remove((min, mindist))
        return (min, mindist)
    
    def isEmpty(self):
        """checks if the list is empty"""
        if len(self.entries) <= 0:
            return True
        else:
            return False
        
    def hasNode(self, nodeID):
        """checks if that nodeID is in the list"""
        for (nodes, dist) in self.entries:
            if (nodes.ID == nodeID):
                return True
        return False
    
class astar:
    
    def __init__(self, startName, destName):
        self.startNode = node(startName)
        self.destNode = node(destName)
        self.openList = myList()
        self.closedList = myList()
        self.currentNode = None
        self.currentDist = 0
        
    def getRoute(self):
        self.openList.add(self.startNode, 0)
        
        while(not self.openList.isEmpty()):
            (self.currentNode, self.currentDist) = self.openList.removeMin()
            self.currentNode.expand()
            
            if (self.currentNode.name == self.destNode.name):
                return "found"
            
            self.closedList.add(self.currentNode, self.currentDist)