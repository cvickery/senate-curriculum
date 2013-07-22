#! /usr/bin/env python
"""
  Process the latest offerings table to give current # of sections/seats/enrollment
  for each approved gened course.
"""

"""
CREATE TABLE offerings (
    term           TEXT,
    session        TEXT,
    term_code      NUMBER,
    term_name      TEXT,
    term_abbr      TEXT,
    discipline     TEXT,
    course_number  TEXT,
    class_section  TEXT,  --  Added 2013-07-20
    component      TEXT,
    status         TEXT,
    seats          NUMBER,
    enrollment     NUMBER  );
"""

import os
import sqlite3
import psycopg2

# Find latest offerings db, and open it

dbs = sorted(os.listdir('../db/'))
db_file = '../db/' + dbs[-1]
print('Using {}'.format(db_file))
conn = sqlite3.connect(db_file)
conn.row_factory = sqlite3.Row
c = conn.cursor()
c.execute('select * from offerings')
row = c.fetchone()
print(row.keys())
o_cursor = conn.cursor()
