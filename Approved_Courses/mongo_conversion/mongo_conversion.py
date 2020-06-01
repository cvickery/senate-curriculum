#! /usr/local/bin/python3
""" Build a MongoDB collection of approved courses and their reasons therefore.
"""
# /Library/Server/Web/Data/Sites/senate.qc.cuny.edu/Curriculum/Approved_Courses/mongo_conversion/mongo_conversion.py

import psycopg2
from psycopg2.extras import NamedTupleCursor
from recordclass import recordclass, asdict
from pymongo import MongoClient
import argparse

parser = argparse.ArgumentParser()
parser.add_argument('--debug', '-d', action='store_true')
parser.add_argument('--verbose', '-v', action='store_true')
args = parser.parse_args()

client = MongoClient()
db = client.gened_courses
mongo_courses = db.courses
mongo_courses.drop()

course_t = recordclass('course',
                       'discipline catalog_number course_ids, designations',
                       defaults=[[], []])
designation_t = recordclass('designation',
                            'name type reason is_primary',
                            defaults=[False])

curric_db = psycopg2.connect('dbname=curric')
curric_cursor = curric_db.cursor(cursor_factory=NamedTupleCursor)
courses_db = psycopg2.connect('dbname=cuny_courses')
courses_cursor = courses_db.cursor(cursor_factory=NamedTupleCursor)

curric_cursor.execute("""
  select c.discipline, c.course_number, t.abbr as name, k.abbr as type, c.is_primary, c.reason
  from course_designation_mappings c, proposal_types t, proposal_classes k
  where t.id = c.designation_id
  and k.id = t.class_id
  order by discipline, course_number""")

# Build/update mongo documents for the individual courses, collecting all their designations and
# reasons.

num_courses = 0
for row in curric_cursor.fetchall():
  courses_cursor.execute(f"""select course_id,
                                    discipline,
                                    catalog_number,
                                    title,
                                    designation,
                                    attributes,
                                    course_status
                             from courses
                             where institution = 'QNS01'
                               and discipline = '{row.discipline}'
                               and catalog_number ~ '^{row.course_number}.?$'
                          """)
  course_ids = [r.course_id for r in courses_cursor.fetchall()]
  designation = asdict(designation_t._make([row.name, row.type, row.reason, row.is_primary]))
  course = asdict(course_t._make([row.discipline, row.course_number, course_ids, designation]))
  if args.debug:
    print(course)
  mongo_courses.insert_one(course)
  num_courses += 1

if args.verbose:
  print(f'{num_courses:,} rows inserted into gened_courses.courses')
