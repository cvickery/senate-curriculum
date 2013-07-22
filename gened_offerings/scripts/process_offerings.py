#! /usr/bin/env python
"""
  Process the latest offerings table to give current # of sections/seats/enrollment
  for each approved gened course.
"""
import os
import sqlite3
import psycopg2

# Find latest offerings db, and open it

dbs = sorted(os.listdir('../db/'))
db_file = dbs[-1]
print('Using {}'.format(db_file))
conn = sqlite3.connect(db_file)
o_cursor = conn.cursor()
