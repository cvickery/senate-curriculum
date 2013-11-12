#! /usr/bin/env python3
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
import re
import sqlite3
import psycopg2
import collections
from collections import namedtuple
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
offering_file = '1901-01-01'
for file in os.listdir('../db/'):
  if re.match("\d{4}", file):
    if file > offering_file: offering_file = file
if offering_file == '1901-01-01': exit("No enrollments database found")
offering_file = '../db/' + offering_file
print('Offerings db: {}'.format(offering_file))
offering_conn = sqlite3.connect(offering_file)
offering_conn.row_factory = sqlite3.Row
offering_curs = offering_conn.cursor()
offering_curs.execute('select date_loaded from enrollments group by date_loaded')
date_loaded = ''
for row in offering_curs:
  if date_loaded != '':
    print('Warning: replacing date_loaded({}) with{}.'.format(date_loaded, row['date_loaded']))
  date_loaded = row['date_loaded']
if date_loaded == '': exit("Unable to determine date_loaded")
print("Offerings last updated on", date_loaded)
curric_curs.execute("""
    update update_log
    set updated_date = '{}'
    where table_name = 'enrollments'
    """.format(date_loaded))
offering_curs.execute('select term_code, term_name from offerings group by term,term_name')

# Recreate the curric.terms table
curric_curs.execute('drop table if exists enrollment_terms cascade')
curric_curs.execute("""
create table enrollment_terms (
  term_code integer primary key,
  term_name text)
""")
for row in offering_curs:
  curric_curs.execute("insert into enrollment_terms values({}, '{}')".format(row['term_code'],
                                                                  row['term_name']))

# Create curric.enrollments for courses of interest
curric_curs.execute('drop table if exists enrollments')
curric_curs.execute("""
create  table enrollments(
        term_code     integer references enrollment_terms,
        discipline    text    references cf_academic_organizations(abbr),
        course_number integer not null,
        suffixes      text    default '',
        section       integer,
        seats         integer,
        enrollment    integer,
        primary key   (term_code, discipline, course_number))
""")

# Create dictionary, indexed by term_code, discipline, course_number, of enrollment info
courses = {}
offering_curs.execute('select * from offerings')
for row in offering_curs:
  term_code = row['term_code']
  discipline = row['discipline']
  empty, course_number, suffix = re.split('(\d+)', row['course_number'])
  course_key = (term_code, discipline, course_number)
  if course_key not in courses:
    courses[course_key] = namedtuple('Course', 'suffixes etc')
    courses[course_key].suffixes = set()
  courses[course_key].suffixes.add(suffix)

curric_curs.close()
curric_db.commit()
