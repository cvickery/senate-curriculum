#! /usr/local/bin/python3
"""
  Generate course_enrollments table for all offered courses based on current 805 extract, which
  includes final totals for previous semesters.
  No daily history tracking here: see update_enrollments.py for that.
    Views:
      offered_courses:  Coalesce sections and instruction modes, giving courses
      offered_gened:    Extract GenEd courses from offered_courses
"""

import argparse
import os
import re
import sqlite3
import psycopg2
from pprint import pprint
from collections import namedtuple
from psycopg2.extras import NamedTupleConnection

# Determine what curric db to use and connect to it
parser = argparse.ArgumentParser(description='Process enrollments db')
parser.add_argument('-d', '--db_name', nargs='?', default='test_curric')
args = parser.parse_args()
curric_name = args.db_name
try:
    curric_db = psycopg2.connect("host='localhost' dbname='{}' user='vickery'".format(curric_name))
except:
    exit("  Unable to connect to {}".format(curric_name))
curric_curs = curric_db.cursor(cursor_factory=psycopg2.extras.NamedTupleCursor)

print('Curriculum db: {}'.format(curric_name))

## Find latest enrollments db, and open it
enrollment_file = '1901-01-01'
for file in os.listdir('../db/'):
  if re.match("\d{4}", file):
    if file > enrollment_file: enrollment_file = file
if enrollment_file == '1901-01-01': exit("No enrollments database found")
enrollment_file = '../db/' + enrollment_file
print('enrollments db: {}'.format(enrollment_file))
enrollment_conn = sqlite3.connect(enrollment_file)
enrollment_conn.row_factory = sqlite3.Row
enrollment_curs = enrollment_conn.cursor()

## Sanity check: there must be only one value for date_loaded
enrollment_curs.execute('select date_loaded from enrollments group by date_loaded')
date_loaded = ''
for row in enrollment_curs:
  if date_loaded != '':
    print('Warning: replacing date_loaded({}) with{}.'.format(date_loaded, row['date_loaded']))
  date_loaded = row['date_loaded']
if date_loaded == '': exit("Unable to determine date_loaded")
print("enrollments last updated on", date_loaded)

## Indicate the new date for updating enrollments
curric_curs.execute("""
    update update_log
    set updated_date = '{}'
    where table_name = 'enrollments'
    """.format(date_loaded))

## Get list of disciplines with scheduled courses: others will be disregarded
disciplines = set()
enrollment_curs.execute("select discipline from enrollments group by discipline order by discipline")
for row in enrollment_curs:
  disciplines.add(row['discipline'])

ignore_disciplines = set(['CLUBS', 'ELI'])

enrollment_curs.execute('select term_code, term_name from enrollments group by term,term_name')

# Recreate the curric.terms table
# TODO: Instead of replacing the table, add terms as they become available
curric_curs.execute('drop table if exists enrollment_terms cascade')
curric_curs.execute("""
create table enrollment_terms (
  term_code integer primary key,
  term_name text)
""")
for row in enrollment_curs:
  curric_curs.execute("insert into enrollment_terms values({}, '{}')".format(row['term_code'],
                                                                  row['term_name']))

# Create tuple of sections, seats, enrollment for each section/component of each course, plus total
# for the course, for every semester of record.

# Create curric.enrollments for courses of interest, and two views thereof.
curric_curs.execute('drop table if exists course_enrollments cascade')
curric_curs.execute("""
create  table course_enrollments
(
  term_code         integer references enrollment_terms,
  discipline        text    references discp_dept_div(discipline),
  course_number     integer not null,
  component         text    not null,
  instruction_mode  text    default '',
  suffixes          text    default '',
  num_sections      integer not null,
  num_seats         integer not null,
  enrollment        integer not null,
  primary key       (term_code, discipline, course_number, component, instruction_mode)
);

drop view if exists offered_courses;
drop view if exists offered_gened;

create view offered_courses as
(
  select    term_code,
            discipline,
            course_number,
            component,
            suffixes,
            sum(num_sections)   as sections,
            sum(num_seats)      as seats,
            sum(enrollment)     as enrollment
  from      course_enrollments
  group by  term_code, discipline, course_number, component, suffixes
  having    sum(num_sections) > 0
  order by  term_code, discipline, course_number, component
);

create view offered_gened as
(
  select  o.term_code,
          m.discipline,
          m.course_number,
          o.component,
          o.suffixes,
          t.abbr            as designation,
          m.is_primary,
          o.sections,
          o.seats,
          o.enrollment
  from  course_designation_mappings m, proposal_types t, offered_courses o
  where t.id = m.designation_id
  and   m.discipline    = o.discipline
  and   m.course_number = o.course_number
  order by discipline, course_number, component, designation
);
""")

# Create dictionaries of enrollment info
# Accumulate # of sections, seats, and enrollment separately for each course and for each
# section and each instruction mode of a course. Courses are indexed by {term, discipline, number};
# sections by {term, discipline, number, section, component, instruction_mode}
# Each row is a separate section; all sections of a course are contiguous, by semester.


#courses       = {}     # Dictionary indexed by term_code, discipline, course_number
#sections      = {}     # Dictionary indexed by course_key + section, component, instruction_mode
term_code         = 0
discipline        = ''
course_number     = 0
component         = ''
instruction_mode  = ''
suffixes          = set()   # Set of suffixes: {'', 'W', 'H'}
num_sections      = 0
num_seats         = 0
num_enrolled      = 0
this_index        = ''      # term_code + discipline + course_number

enrollment_curs.execute("""
select * from enrollments order by term_code, discipline, course_number, component, instruction_mode
""")
for row in enrollment_curs:
  new_discipline  = row['discipline']
  if new_discipline not in disciplines or new_discipline in ignore_disciplines:
    continue
  new_term_code   = row['term_code']
  empty, new_course_number, suffix = re.split('(\d+)', row['course_number'])
  if suffix == '':
    suffix = '-'
  section               = row['class_section']
  new_component         = row['component']
  seats                 = row['seats']
  enrollment            = row['enrollment']
  new_instruction_mode  = row['instruction_mode']
  new_index             = '{} {} {} {} {}'.format(new_term_code,
                                            new_discipline,
                                            new_course_number,
                                            new_component,
                                            new_instruction_mode)
  if new_index != this_index:
    if this_index != '':
      # Write course data to curric.course_enrollments
      suffix_str = ''
      for a_suffix in sorted(suffixes):
        suffix_str += a_suffix
      curric_curs.execute("""
  insert into course_enrollments values(
          {},   --  term_code
          '{}', --  discipline
          {},   --  course_number
          '{}', --  component
          '{}', --  instruction_mode
          '{}', --  suffixes
          {},   --  num_sections
          {},   --  num_seats
          {})   --  enrollment
""".format(term_code, discipline, course_number, component, instruction_mode,
           suffix_str, num_sections, num_seats, num_enrolled))
    this_index        = new_index
    term_code         = new_term_code
    discipline        = new_discipline
    course_number     = new_course_number
    component         = new_component
    instruction_mode  = new_instruction_mode
    suffixes          = set(suffix)
    num_sections      = 1
    num_seats         = seats
    num_enrolled      = enrollment
  else:
    suffixes.add(suffix)
    num_sections  +=  1
    num_seats     +=  seats
    num_enrolled  +=  enrollment

curric_curs.close()
curric_db.commit()
