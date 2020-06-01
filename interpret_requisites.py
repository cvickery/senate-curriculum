#! /usr/local/bin/python3
""" Extract formal descriptions of course requisite structures.
"""

# /Users/vickery/CUNY_Courses/interpret_requisites.py

import re
import csv
from collections import namedtuple
from pprint import pprint

from pymongo import MongoClient

db = MongoClient()

other_requisites = dict()
Details = namedtuple('Details',
                     """key
                        type
                        include
                        recall
                        interp
                        course_list
                        career
                        cond_code
                        operator
                        value
                        test_id
                        component
                        score
                        max_age
                        select_method
                     """)
with open('./other_queries/RQ_LN_DETL_TBL-26286875.csv') as details_file:
  reader = csv.reader(details_file)
  cols = None
  for line in reader:
    if cols is None:
      cols = [val.lower().replace(' ', '_').replace('/', '_').replace('-', '') for val in line]
      cols[0] = 'requirement'
      Row = namedtuple('Row', cols)
    else:
      row = Row._make(line)
      other_requisites[int(row.requirement)] = Details._make([row.key,
                                                             row.dtl_type,
                                                             row.incl_mode,
                                                             row.recall,
                                                             row.interp,
                                                             row.crse_lst,
                                                             row.career,
                                                             row.cond_code,
                                                             row.operator,
                                                             row.value,
                                                             row.test_id,
                                                             row.component,
                                                             row.score,
                                                             row.max_age,
                                                             row.select_method])

this_course = None
with open('./other_queries/MORE_COMPLETE_REQUISITES.csv') as csvfile:
  reader = csv.reader(csvfile)
  cols = None
  for line in reader:
    if cols is None:
      if 'Institution' == line[0]:
        cols = [val.lower().replace(' ', '_').replace('/', '_').replace('-', '') for val in line]
        for col_name in cols:
          more_dupes = True
          while more_dupes:
            first_index = cols.index(col_name)
            try:
              next_index = cols.index(col_name, first_index + 1)
              cols[next_index] = f'{col_name}_{next_index}'
            except ValueError:
              more_dupes = False
        Row = namedtuple('Row', cols)
    else:
      row = Row._make(line)
      course = (row.institution, row.subject, row.catalog)
      if course != this_course:
        if this_course is not None:
          # Report the current course requisites
          if requisites.strip() != '':
            requisites = re.sub(' {2,}', ' ', requisites).replace('( ', '(').replace(' )', ')')
            print(f'{this_course} "{requisites}"\n==>{description}\n')
        # Start new course
        this_course = course
        description = row.descr_of_pre_corequisites
        requisites = ''
      requisites += f" {row.logical_connector.upper()} {row.lp} {row.rqs_type.replace('-Rqs', ':')}"
      requisites += f" {row.req_subject} {row.req_catalog} "
      if row.min_grade != '':
        requisites += f'(min {row.min_grade})'
      requisites += f"{row.cond_code} {row.operator.replace(' or ', '').replace('Not Equal', '!=')}"
      requisites += f" {row.value}"
      if row.consent.startswith('Dept'):
        requisites += ' with Department Approval '
      if row.consent.startswith('Instr'):
        requisites += ' with Instructor Approval '
      requisites += row.rp
      if row.rqrmnt != '':
        try:
          requisites += f'\n+++ {other_requisites[int(row.rqrmnt)]}'
        except KeyError:
          requisites += f'\n*** {row.rqrmnt} NOT FOUND IN RQ_LN_DETL_TBL'
