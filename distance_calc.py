#!/usr/bin/env python
import MySQLdb

import MySQLdb
import sys

debugLevel = 0

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
        return ((self.x - node.x)**2 + (self.y - node.y)**2 + (self.z - node.z)**2)
    
    def expand(self):
        """selects all adjancents nodes"""
        cursor.execute("SELECT solarSystemName " +\
                       "FROM mapsolarSystems AS t1, mapsolarsystemjumps AS t2 " +\
                       "WHERE t1.solarSystemID = t2.toSolarSystemID " +\
                       "AND t2.fromSolarSystemID = %i" % (self.ID))
        expandList = cursor.fetchall()
        returnList = []
        for toSolarSystemName in expandList:
            returnList.append(toSolarSystemName[0])
        return returnList
    
    def traceRoute(self):
        trace = []
        node = self
        while(node.predecessor != None):
            trace.append(node.name)
            node = node.predecessor
        trace.append(node.name)
        return trace
    
    def printNode(self):
        print("%s (%i)" % (self.name, self.ID))
    
class myList:
    """Exploration list for A*"""
    
    def __init__(self):
        self.entries = []
    
    def add(self, node, dist):
        """adds an element to the list"""
        self.entries.append((node, dist))
        
    def removeMin(self):
        """will return and remove the minimal node"""
        minDist = 0
        minNode = None
        once = True
        for (nodes, dist) in self.entries:
            if ((dist < minDist) or (once == True)):
                minNode = nodes
                minDist = dist
                once = False
        
        if (debugLevel >= 1):
            print ("Removing: %s (%i) - %f" % (minNode.name, minNode.ID, minDist))
        
        self.entries.remove((minNode, minDist))
        return (minNode, minDist)
    
    def isEmpty(self):
        """checks if the list is empty"""
        if len(self.entries) <= 0:
            return True
        else:
            return False
        
    def hasNode(self, name):
        """checks if that nodeID is in the list"""
        for (nodes, dist) in self.entries:
            if (nodes.name == name):
                return True
        return False
    
    def getDistance(self, name):
        """will return the distance to the matching node"""
        for (nodes, dist) in self.entries:
            if (nodes.name == name):
                return dist
    
    def decreaseKey(self, node, newDist):
        for (nodes, dist) in self.entries:
            if (nodes.name == node.name):
                dist = newDist
                nodes.predecessor = node
    
    def printList(self):
        for (node, dist) in self.entries:
            print("%s (%i) - %f" % (node.name, node.ID, dist))
    
class astar:
    """a* calculation based on coordinates"""

    def __init__(self, startName, destName):
        self.startNode = node(startName)
        self.destNode = node(destName)
        self.openList = myList()
        self.closedList = myList()
        self.currentNode = None
        self.currentDist = 0
        
    def getRoute(self):
        global debugLevel
        self.openList.add(self.startNode, 0)

        while(not self.openList.isEmpty()):
            
            if (debugLevel >= 1):
                print("openList (%i)\n============" % (len(self.openList.entries)))
                self.openList.printList()
                print("\n")
                print("closedList (%i)\n============" % (len(self.closedList.entries)))
                self.closedList.printList()
                print("\n")
                
            (self.currentNode, self.currentDist) = self.openList.removeMin()
            
            if (debugLevel >= 1):
                print("currentNode: %s (%i)" % (self.currentNode.name, self.currentNode.ID))
            
            
            
            if (self.currentNode.ID == self.destNode.ID):
                return self.currentNode.traceRoute()
            
            expandList = self.currentNode.expand()
            for name in expandList:
                if self.closedList.hasNode(name):
                    continue
                newNode = node(name)
                newDist = self.currentDist + newNode.distance(self.currentNode) + newNode.distance(self.destNode)
                
                if (self.openList.hasNode(newNode.name)):
                    if ((self.openList.getDistance(newNode.name)) < newDist):
                        continue
                    else:
                        self.openList.decreaseKey(newNode, newDist)
                else:
                    self.openList.add(newNode, newDist)
                
                newNode.predecessor = self.currentNode
            
            self.closedList.add(self.currentNode, self.currentDist)