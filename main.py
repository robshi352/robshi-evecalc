#!/usr/bin/env python

#import hotshot
#prof = hotshot.Profile("eveprof")
#prof.start()

import MySQLdb
import distance_calc
import sys
import getopt


from distance_calc import *
from agentLocator import *


distance_calc.debugLevel = 0

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



class Usage(Exception):
    def __init__(self, msg):
        self.msg = msg

def main(argv=None):
    if argv is None:
        argv = sys.argv
    try:
        try:
            opts, args = getopt.getopt(argv[1:], "h", ["help"])
        except getopt.error, msg:
             raise Usage(msg)
        # more code, unchanged
    except Usage, err:
        print >>sys.stderr, err.msg
        print >>sys.stderr, "for help use --help"
        return 2
    
    ##################################
    
    myAStar = astar()
    myAStar.newRoute("Jita", "74L2-U")
    #route = myAStar.getRoute()
    #print("%s - %s: %i Jumps" % (route[0], route[-1], len(route) - 1))
    #
    #myAStar.newRoute("Halaima", "Jita")
    #route = myAStar.getRoute()
    #print("%s - %s: %i Jumps" % (route[0], route[-1], len(route) - 1))
    
    myLocator = agentLocator()
    myLocator.set("Caldari Provisions", "Halaima")
    agentList = myLocator.locate()
    
    for (level, quality, systemName, sec) in agentList:
                myAStar.newRoute("Halaima", systemName)
                route = myAStar.getRoute()
                print("%s (%.2f) (%i) - L%iQ%i" % (systemName, sec, len(route) - 1, level, quality))

    
    ####################################
    
if __name__ == "__main__":
    x = main()
    sys.stdout.flush()
    cursor.close()
    #prof.stop()
    #prof.close()
    sys.exit(x)










