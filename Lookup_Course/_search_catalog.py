#! /usr/local/bin/python3
""" Search cuny_courses.courses for course catalog entries.
"""
import sys
import os
import re
import datetime
import psycopg2
from psycopg2.extras import NamedTupleCursor
import json

# For unit testing
import argparse

# CGI stuff -- with debugging
import cgi
import cgitb
cgitb.enable()


# oops()
# -------------------------------------------------------------------------------------------------
def oops(msg):
  """ Die because something went oops.
  """
  print(f"""
    <h1>Unable To Lookup Course</h1>
    <h2>{msg}</h2>
    """)
  exit()


# EXECUTION STARTS HERE
# =================================================================================================
# Get form data ... from command line if unit testing.
""" Web page headers
"""
args = cgi.FieldStorage()
search_string = args.getvalue('search_string',
                              default='<span class="error">Missing Search String</span>')
if search_string is None:
  oops('Missing search string.')

parts = search_string.split()
if len(parts) < 2 or len(parts) > 3:
  oops('Invalid search string')

institution = parts[0]
if len(parts) == 2:
  discipline, catalog_number = re.match(r'^\s*([a-z]+)-?(.+)\s*$', parts[1], re.I).groups()
else:
  discipline, catalog_number = parts[1], parts[2]

catalog_number = re.match(r'^\s*(\*|[\d\.]+)\D*$', catalog_number).group(1)

status_str = "course_status = 'A' and can_schedule = 'Y'"

if institution != '*':
  institution_str = f"and institution ~* '{institution}'"
else:
  institution_str = ''

if catalog_number != '*':
  under = float(catalog_number) - 0.5
  over = under + 1.0
  cat_num_str = """and numeric_part(catalog_number) > {} and numeric_part(catalog_number) < {}
                """.format(under, over)
else:
  cat_num_str = ''

query = f"""
  select course_id, offer_nbr, cuny_subject, course_status,
         institution, discipline, catalog_number, title,
         components,
         min_credits, max_credits,
         description,
         requisites,
         designation,
         attributes
  from courses
  where {status_str}
  {institution_str}
  and discipline ~* %s
  {cat_num_str}
  order by numeric_part(catalog_number)
  """
conn = psycopg2.connect('dbname=cuny_courses user=vickery')
cursor = conn.cursor(cursor_factory=NamedTupleCursor)
cursor2 = conn.cursor(cursor_factory=NamedTupleCursor)  # For cross-listing check
cursor.execute(query, (discipline, ))
return_list = []
for row in cursor.fetchall():
  hours_list = [f'{c[1]:.1f} hr. {c[0].lower()}' for c in row.components]
  hours_str = ', '.join(hours_list)
  if row.min_credits == row.max_credits:
    credits_str = f'{row.min_credits:.1f} cr.'
  else:
    credits_str = f'{row.min_credits:.1f} to {row.max_credits:.1f} cr.'
  if row.requisites == '':
    requisites_str = 'No Requisites.'
  else:
    requisites_str = f'Requisites: {row.requisites}.'
  if row.attributes == '':
    attributes_str = 'None'
  else:
    attributes_str = f'Attributes: {row.attributes}.'
  if row.course_status == 'A':
    status_str = 'Active'
  else:
    status_str = '<span class="warning">Inactive</span>'
  cursor2.execute(f"""
                  select discipline, catalog_number, title
                  from courses
                  where course_id = {row.course_id}
                    and offer_nbr != {row.offer_nbr}
                  """)
  cross_listed_str = ''
  if cursor2.rowcount > 0:
    cross_listed_str = """
    <p><strong class="warning">NOTE</strong> This course is cross-listed with:</p><ul>
    """
    for xlist in cursor2.fetchall():
      cross_listed_str += f'<li>{xlist.discipline} {xlist.catalog_number} {xlist.title}</li>'
    cross_listed_str += '</ul>'

  course_html = f"""
<style type='text/css'>
  *         {{font-family: sans-serif; color:green;}}
  .error    {{color:red;}}
  .warning  {{color: #f33;}}
</style>
<div>
  <p class="course-line">
    <strong>
      {row.institution.strip('012')} {row.discipline} {row.catalog_number}:
    </strong>
    {row.title}
  </p>
  <p class="hours-credits-requisites-line">
    {hours_str}; {credits_str}; {requisites_str}
  </p>
  <p class="course-description">{row.description}</p>
  <p class="metadata-line">
    Designation: {row.designation}; {attributes_str};
    Status: {status_str}
  </p>
  <p class="cuny-line">
    CUNY: course_id={row.course_id:06}; offer_nbr={row.offer_nbr}; subject={row.cuny_subject}
  </p>
  {cross_listed_str}
</div>
  """
  return_list.append(course_html)

html_page = """
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title>Ajax Course Search</title>
  </head>
  <body>
    {}
  </body>
</html>
""".format('<hr/>'.join(return_list))

if os.getenv('REQUEST_METHOD') == 'GET':
  sys.stdout.buffer.write("Content-Type: text/html; charset=utf-8\r\n\r\n".encode('utf-8'))
  sys.stdout.buffer.write(html_page.encode('utf-8'))
else:
  sys.stdout.buffer.write("Content-Type: text/json; charset=utf-8\r\n".encode('utf-8'))
  sys.stdout.buffer.write("X-Content-Type-Options: nosniff\r\n".encode('utf-8'))
  sys.stdout.buffer.write("Access-Control-Allow-Origin: https://pmakerdev.qc.cuny.edu\r\n\r\n"
                          .encode('utf-8'))
  sys.stdout.buffer.write(json.dumps(return_list).encode('utf-8'))
