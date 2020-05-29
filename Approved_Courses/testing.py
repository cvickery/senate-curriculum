#! /usr/local/bin/python3
""" Currently active courses that have the WRIC attribute.
"""
import sys
from datetime import datetime

# For curric db access
import psycopg2
from psycopg2.extras import NamedTupleCursor

# CGI stuff -- with debugging
import cgi
import cgitb
from pprint import pprint
cgitb.enable(display=0, logdir='./debug')

DEBUG = False

passwd = open('./.view_only_passwd', 'r').read().strip(' \n')
conn = psycopg2.connect(f'dbname=cuny_courses user=view_only password={passwd}')
cursor = conn.cursor(cursor_factory=NamedTupleCursor)
cursor.execute("""
  select discipline, catalog_number, title
  from courses
  where institution = 'QNS01'
  and attributes ~* 'WRIC'
  and course_status = 'A'
  order by discipline, catalog_number
""")

table = '<table><tr><th>Course</th><th>Title</th></tr>'
table += '\n'.join([f'<tr><td>{row.discipline} {row.catalog_number}</td><td>{row.title}</td></tr>'
                   for row in cursor.fetchall()])
table += '</table>'
table = table.replace('&', '&amp;')
cursor.execute("select update_date from updates where table_name = 'courses'")
update_date = datetime.fromisoformat(cursor.fetchone().update_date).strftime('%B %d, %Y')
print('Content-Type: text/html; charset=UTF-8\r\n\r\n')
html_page = f"""
  <!DOCTYPE html>
  <html>
    <head>
      <title>Active Writing Intensive Courses</title>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <link rel="stylesheet" href="./css/writing_intensive.css" />
    </head>
    <body>
      <h1>Active Writing Intensive Courses at Queens College</h1>
      <h2>List was last updated {update_date}</h2>
      {table}
    </body>
  </html>"""
print(html_page)
