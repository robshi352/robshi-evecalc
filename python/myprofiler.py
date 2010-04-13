#!/usr/bin/env python

import hotshot, hotshot.stats, main

prof = hotshot.Profile("eveprof")
prof.runcall(main.main)
prof.close()
stats = hotshot.stats.load("eveprof")
stats.strip_dirs()
stats.sort_stats('time', 'calls')
stats.print_stats(20)
