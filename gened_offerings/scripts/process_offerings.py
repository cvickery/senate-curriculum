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

import argparse
import os
import sqlite3
import psycopg2
import collections
from psycopg2.extras import NamedTupleConnection

# Determine what curric db to use and connect to it
parser = argparse.ArgumentParser(description='Process Offerings db')
parser.add_argument('-d', '--db_name', nargs='?', default='test_curric')
args = parser.parse_args()
curric_name = args.db_name
try:
    curric_db = psycopg2.connect("host='localhost' dbname='{}' user='vickery'".format(curric_name))
except:
    exit("  Unable to connect to {}".format(curric_name))
curric_curs = curric_db.cursor(cursor_factory=psycopg2.extras.NamedTupleCursor)

print('Curriculum db: {}'.format(curric_name))

# Find latest offerings db, and open it

offering_dbs = sorted(os.listdir('../db/'))
offering_file = '../db/' + offering_dbs[-1]
print('Offerings db: {}'.format(offering_file))
offering_conn = sqlite3.connect(offering_file)
offering_conn.row_factory = sqlite3.Row
offering_curs = offering_conn.cursor()
offering_curs.execute('select term_code, term_name from offerings group by term,term_name')

# Recreate the curric.terms table
curric_curs.execute('drop table if exists terms')
curric_curs.execute("""
create table terms (
  term_code integer primary key,
  term_name text)
""")
for row in offering_curs:
  curric_curs.execute("insert into terms values({}, '{}')".format(row['term_code'],
                                                                  row['term_name']))

# Create curric.enrollments for courses of interest
curric_curs.execute('drop table if exists enrollments')
#curric_curs.execute("""
#create table enrollments(


curric_curs.close()
curric_db.commit()
